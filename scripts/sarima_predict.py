#!/usr/bin/env python3
import argparse, json, warnings
import pandas as pd
import numpy as np
from dateutil.relativedelta import relativedelta
from statsmodels.tsa.statespace.sarimax import SARIMAX

warnings.filterwarnings("ignore")

parser = argparse.ArgumentParser()
parser.add_argument('csv_path')
parser.add_argument('--order', default='4,1,4')              # p,d,q
parser.add_argument('--seasonal', default='1,0,0,12')        # P,D,Q,s (yearly seasonality)
parser.add_argument('--steps', type=int, default=24)         # forecast horizon (months)
parser.add_argument('--harvest_months', default='12,1,2,3')  # Default Dec–Mar (configurable)
parser.add_argument('--mode', default='auto', choices=['auto','monthly','annual'])
parser.add_argument('--log', action='store_true', default=True)  # log1p transform
args = parser.parse_args()

order = tuple(map(int, args.order.split(',')))
P, D, Q, s = map(int, args.seasonal.split(','))
seasonal_order = (P, D, Q, s)
HARVEST_MONTHS = set(int(x) for x in args.harvest_months.split(',') if x.strip())

# --- Load & prepare data ---
df = pd.read_csv(args.csv_path, parse_dates=['harvest_date'])
if df.empty or 'harvest_weight_kg' not in df.columns:
    # Always return a valid, minimal JSON
    print(json.dumps({
        "predicted_quantity": 0.0,
        "predicted_date": pd.Timestamp.today().date().isoformat(),
        "monthly_predictions": [],
        "season_total": {
            "season_start": None,
            "season_end": None,
            "predicted_total": 0.0
        }
    }))
    raise SystemExit(0)

df = df.sort_values('harvest_date')

# Build continuous monthly series with zeros for missing months
start = df['harvest_date'].min().replace(day=1)
end   = df['harvest_date'].max().replace(day=1)
all_months = pd.date_range(start=start, end=end, freq='MS')

monthly = (df.set_index('harvest_date')['harvest_weight_kg']
             .groupby(pd.Grouper(freq='MS')).sum()
             .reindex(all_months, fill_value=0.0))

# Helper: next season year based on last observed month
last_idx = monthly.index[-1]
last_year = last_idx.year
season_year = last_year if last_idx.month < min(HARVEST_MONTHS) else last_year + 1
season_start_ts = pd.Timestamp(season_year, min(HARVEST_MONTHS), 1)
season_end_ts   = pd.Timestamp(season_year, max(HARVEST_MONTHS), 1) + relativedelta(months=+1, days=-1)

def safe_output(pred_series):
    """Builds the final JSON with both flat and rich fields."""
    # Enforce harvest-window: 0 outside harvest months
    pred_series = pred_series.where(pred_series.index.month.astype(int).isin(HARVEST_MONTHS), 0.0)
    pred_series = pred_series.fillna(0.0).replace([np.inf, -np.inf], 0.0).clip(lower=0.0)

    # Monthly array
    monthly = [
        {"predicted_date": d.date().isoformat(), "predicted_quantity": round(float(v), 2)}
        for d, v in pred_series.items()
    ]

    # Season slice + total
    season_slice = pred_series[(pred_series.index >= season_start_ts) & (pred_series.index <= season_end_ts)]
    season_total = round(float(season_slice.sum()), 2) if not season_slice.empty else 0.0

    # Pick the first non-zero harvest month as the flat keys (fallback to first upcoming Oct if all zero)
    nonzero = season_slice[season_slice > 0]
    if len(nonzero) > 0:
        first_date = nonzero.index[0].date().isoformat()
        first_qty = round(float(nonzero.iloc[0]), 2)
    else:
        # If no non-zero in forecast horizon, point to next season’s first harvest month with 0.0
        first_date = season_start_ts.date().isoformat()
        first_qty = 0.0

    return {
        "predicted_quantity": first_qty,              # ← Laravel expects these
        "predicted_date": first_date,                 # ← Laravel expects these
        "monthly_predictions": monthly,
        "season_total": {
            "season_start": season_start_ts.date().isoformat(),
            "season_end": season_end_ts.date().isoformat(),
            "predicted_total": season_total
        }
    }

# If all historical data are zeros, skip modeling and return zeros safely
if float(monthly.sum()) == 0.0:
    # Still create a monthly horizon after the last observed month
    horizon = pd.date_range(start=last_idx + relativedelta(months=+1), periods=args.steps, freq='MS')
    zeros = pd.Series(0.0, index=horizon)
    print(json.dumps(safe_output(zeros)))
    raise SystemExit(0)

def choose_orders(y):
    """Small grid search to choose ARIMA/SARIMA orders by AIC."""
    candidates = [
        ((1,0,0), (1,0,0,s)),
        ((2,0,0), (1,0,0,s)),
        ((1,1,0), (1,0,0,s)),
        ((1,0,1), (1,0,0,s)),
        ((1,0,0), (0,1,0,s)),
    ]
    best = None
    best_aic = float('inf')
    for (o, so) in candidates:
        try:
            fit = SARIMAX(y, order=o, seasonal_order=so,
                          enforce_stationarity=False, enforce_invertibility=False).fit(disp=False)
            if fit.aic < best_aic:
                best_aic = fit.aic
                best = (fit, o, so)
        except Exception:
            continue
    return best

def fit_and_forecast_monthly(y):
    y_in = y.copy()
    if args.log:
        y_in = np.log1p(y_in.clip(lower=0.0))
    best = choose_orders(y_in)
    if not best:
        raise RuntimeError('No model fit')
    fit, _, _ = best
    fc = fit.get_forecast(steps=args.steps)
    pred = fc.predicted_mean
    if args.log:
        pred = np.expm1(pred)
    return pred

def fit_and_forecast_annual(y_monthly):
    # Build annual season totals per season year
    months_sorted = sorted(HARVEST_MONTHS)
    wraps = (12 in HARVEST_MONTHS and 1 in HARVEST_MONTHS)
    def season_year(ts):
        return ts.year + 1 if wraps and ts.month == 12 else ts.year
    dfm = y_monthly.reset_index()
    dfm['season_year'] = dfm['harvest_date'].apply(season_year)
    dfm['month'] = dfm['harvest_date'].dt.month
    dfm = dfm[dfm['month'].isin(HARVEST_MONTHS)]
    annual = dfm.groupby('season_year')['harvest_weight_kg'].sum()
    if annual.empty:
        raise RuntimeError('No annual data')
    y_in = annual.copy()
    if args.log:
        y_in = np.log1p(y_in.clip(lower=0.0))
    # Simple ARIMA on annual totals
    candidates = [(1,0,0), (2,0,0), (1,1,0)]
    best = None
    best_aic = float('inf')
    for o in candidates:
        try:
            fit = SARIMAX(y_in, order=o, seasonal_order=(0,0,0,0),
                          enforce_stationarity=False, enforce_invertibility=False).fit(disp=False)
            if fit.aic < best_aic:
                best_aic = fit.aic
                best = fit
        except Exception:
            continue
    if not best:
        raise RuntimeError('No annual model fit')
    fc = best.get_forecast(steps=1)
    total_next = fc.predicted_mean.iloc[-1]
    if args.log:
        total_next = np.expm1(total_next)
    total_next = float(max(0.0, total_next))

    # Allocate to months using historical month shares
    month_shares = (dfm.groupby('month')['harvest_weight_kg'].sum()).astype(float)
    if month_shares.sum() <= 0:
        weights = {m: 1.0/len(HARVEST_MONTHS) for m in HARVEST_MONTHS}
    else:
        shares = month_shares / month_shares.sum()
        weights = {int(m): float(shares.get(m, 0.0)) for m in HARVEST_MONTHS}

    # Build monthly forecast horizon after last observed month
    horizon = pd.date_range(start=last_idx + relativedelta(months=+1), periods=args.steps, freq='MS')
    pred = pd.Series(0.0, index=horizon)
    for d in pred.index:
        m = int(d.month)
        if m in HARVEST_MONTHS:
            pred.loc[d] = total_next * weights.get(m, 0.0)
    return pred

try:
    # Choose mode
    nonzero_count = int((monthly > 0).sum())
    mode = args.mode
    if mode == 'auto':
        mode = 'annual' if nonzero_count < 10 else 'monthly'

    if mode == 'annual':
        pred = fit_and_forecast_annual(monthly.to_frame(name='harvest_weight_kg'))
    else:
        pred = fit_and_forecast_monthly(monthly)

    print(json.dumps(safe_output(pred)))
except Exception:
    # Last-resort fallback: zeros with valid schema
    horizon = pd.date_range(start=last_idx + relativedelta(months=+1), periods=args.steps, freq='MS')
    zeros = pd.Series(0.0, index=horizon)
    print(json.dumps(safe_output(zeros)))
