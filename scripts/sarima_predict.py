#!/usr/bin/env python3
import argparse, json, warnings
import pandas as pd
import numpy as np
from dateutil.relativedelta import relativedelta
from statsmodels.tsa.statespace.sarimax import SARIMAX

warnings.filterwarnings("ignore")

parser = argparse.ArgumentParser()
parser.add_argument('csv_path')
parser.add_argument('--order', default='4,1,4')           # p,d,q
parser.add_argument('--seasonal', default='1,0,0,12')     # P,D,Q,s (yearly seasonality)
parser.add_argument('--steps', type=int, default=24)      # forecast horizon (months)
parser.add_argument('--harvest_months', default='1,2,3')  # Default Jan–Mar (configurable)
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

series = (df.set_index('harvest_date')['harvest_weight_kg']
            .groupby(pd.Grouper(freq='MS')).sum()
            .reindex(all_months, fill_value=0.0))

# Helper: determine the appropriate season year for prediction
last_idx = series.index[-1]
last_year = last_idx.year
current_month = last_idx.month

# If we're in or past the harvest season, predict for next year's season
# If we're before the harvest season, predict for this year's season
if current_month >= min(HARVEST_MONTHS):
    # We're in or past the harvest season, predict for next year
    season_year = last_year + 1
else:
    # We're before the harvest season, predict for this year
    season_year = last_year

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

    # Pick the first non-zero harvest month as the flat keys (fallback to first upcoming harvest month if all zero)
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
if float(series.sum()) == 0.0:
    # Still create a monthly horizon after the last observed month
    horizon = pd.date_range(start=last_idx + relativedelta(months=+1), periods=args.steps, freq='MS')
    zeros = pd.Series(0.0, index=horizon)
    print(json.dumps(safe_output(zeros)))
    raise SystemExit(0)

# Fit SARIMA
try:
    model = SARIMAX(series, order=order, seasonal_order=seasonal_order,
                    enforce_stationarity=False, enforce_invertibility=False)
    fit = model.fit(disp=False)

    fc = fit.get_forecast(steps=args.steps)
    pred = fc.predicted_mean

    print(json.dumps(safe_output(pred)))
except Exception:
    # Last-resort fallback: zeros with valid schema
    horizon = pd.date_range(start=last_idx + relativedelta(months=+1), periods=args.steps, freq='MS')
    zeros = pd.Series(0.0, index=horizon)
    print(json.dumps(safe_output(zeros)))
