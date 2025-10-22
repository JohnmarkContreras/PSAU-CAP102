#!/usr/bin/env python3
import argparse, json, warnings, os, sys, traceback
import pandas as pd
import numpy as np
import datetime
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from dateutil.relativedelta import relativedelta
from statsmodels.tsa.statespace.sarimax import SARIMAX
from sklearn.metrics import mean_absolute_percentage_error, mean_squared_error
import scipy.stats as stats

warnings.filterwarnings("ignore")

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('csv_path')
    parser.add_argument('--order', default='1,1,1')
    parser.add_argument('--seasonal', default='1,1,1,12')
    parser.add_argument('--steps', type=int, default=24)
    parser.add_argument('--harvest_months', default='1,2,3')
    parser.add_argument('--start_from', default=None)
    args = parser.parse_args()

    order = tuple(map(int, args.order.split(',')))
    P, D, Q, s = map(int, args.seasonal.split(','))
    seasonal_order = (P, D, Q, s)
    HARVEST_MONTHS = set(int(x) for x in args.harvest_months.split(',') if x.strip())

    # --- Load CSV ---
    df = pd.read_csv(args.csv_path, parse_dates=['harvest_date'])
    if df.empty or 'harvest_weight_kg' not in df.columns:
        output = {
            "forecast": {
                "predicted_quantity": 0.0,
                "predicted_date": pd.Timestamp.today().date().isoformat(),
                "monthly_predictions": [],
                "season_total": {
                    "season_start": None,
                    "season_end": None,
                    "predicted_total": 0.0
                }
            },
            "evaluation": None
        }
        print(json.dumps(output))
        sys.exit(0)

    df = df.sort_values('harvest_date')

    # --- Build monthly series ---
    start = df['harvest_date'].min().replace(day=1)
    end = df['harvest_date'].max().replace(day=1)
    all_months = pd.date_range(start=start, end=end, freq='MS')
    series = (
        df.set_index('harvest_date')['harvest_weight_kg']
        .groupby(pd.Grouper(freq='MS')).sum()
        .reindex(all_months, fill_value=0.0)
    )

    # --- NEW: Dynamic next season based on current date ---
    current_ts = pd.Timestamp(datetime.date.today())
    current_year = current_ts.year
    current_month = current_ts.month
    max_harvest_month = max(HARVEST_MONTHS)
    if current_month > max_harvest_month:
        season_year = current_year + 1
    else:
        season_year = current_year

    # Advance if season already passed or complete in data
    last_data_year = series.index[-1].year
    if season_year <= last_data_year:
        season_year = last_data_year + 1

    season_start_ts = pd.Timestamp(season_year, min(HARVEST_MONTHS), 1)
    season_end_ts = pd.Timestamp(season_year, max(HARVEST_MONTHS), 1) + relativedelta(months=1, days=-1)

    # --- NEW: Dynamic steps ---
    last_idx = series.index[-1]
    start_forecast = last_idx + relativedelta(months=1)
    target_start = pd.Timestamp(season_year, min(HARVEST_MONTHS), 1)
    months_to_target = (target_start.year - start_forecast.year) * 12 + (target_start.month - start_forecast.month)
    args.steps = max(args.steps, months_to_target + 12) if months_to_target >= 0 else args.steps

    # --- JSON-safe formatter ---
    def safe_output(pred_series):
        if not isinstance(pred_series.index, pd.DatetimeIndex):
            pred_series.index = pd.date_range(start=pd.Timestamp.today(), periods=len(pred_series), freq='MS')

        existing_months = set(df['harvest_date'].dt.to_period('M'))
        pred_periods = pred_series.index.to_period('M')
        mask = ~pred_periods.isin(existing_months)
        pred_series = pred_series[mask]

        # Keep only harvest months
        pred_series = pred_series.where(pred_series.index.month.astype(int).isin(HARVEST_MONTHS), 0.0)

        # Clean and limit unrealistic values
        pred_series = pred_series.fillna(0.0).replace([np.inf, -np.inf], 0.0)
        pred_series = pred_series.clip(lower=0.0)

        # --- Relaxed cap for realism ---
        max_val = float(df['harvest_weight_kg'].max()) if not df.empty else 0.0
        if max_val > 0:
            cap = max_val * 5.0   # at most 5× historical max
            pred_series = pred_series.clip(upper=cap)
        else:
            pred_series = pred_series.clip(upper=20.0)  # fallback cap for tiny datasets

        # --- Removed smoothing to avoid dilution ---

        # --- Typical harvest day per month using mode ---
        df['day'] = df['harvest_date'].dt.day
        best_day_by_month = df.groupby(df['harvest_date'].dt.month)['day'].apply(lambda x: stats.mode(x)[0]).to_dict()

        monthly = []
        for d, v in pred_series.items():
            month = d.month
            year = d.year
            best_day = int(best_day_by_month.get(month, 1))
            last_day = (d + pd.offsets.MonthEnd(0)).day
            best_day = min(best_day, last_day)
            best_date = pd.Timestamp(year, month, best_day)
            monthly.append({
                "predicted_date": best_date.date().isoformat(),
                "predicted_quantity": round(float(v), 2)
            })

        # --- Season totals ---
        season_slice = pred_series[(pred_series.index >= season_start_ts) & (pred_series.index <= season_end_ts)]
        season_total = round(float(season_slice.sum()), 2) if not season_slice.empty else 0.0

        nonzero = season_slice[season_slice > 0]
        if len(nonzero) > 0:
            first_month = nonzero.index[0]
            best_day = int(best_day_by_month.get(first_month.month, 1))
            last_day = (first_month + pd.offsets.MonthEnd(0)).day
            best_day = min(best_day, last_day)
            first_date = pd.Timestamp(first_month.year, first_month.month, best_day).date().isoformat()
            first_qty = round(float(nonzero.iloc[0]), 2)
        else:
            first_date = season_start_ts.date().isoformat()
            first_qty = 0.0

        return {
            "predicted_quantity": first_qty,
            "predicted_date": first_date,
            "monthly_predictions": monthly,
            "season_total": {
                "season_start": season_start_ts.date().isoformat(),
                "season_end": season_end_ts.date().isoformat(),
                "predicted_total": season_total
            }
        }

    # --- Evaluation and chart ---
    def evaluate_accuracy(pred_df, actual_df, save_path=None):
        pred_df['predicted_date'] = pred_df['predicted_date'].dt.to_period('M').dt.to_timestamp('MS')
        actual_df['harvest_date'] = actual_df['harvest_date'].dt.to_period('M').dt.to_timestamp('MS')

        actual_monthly = (
            actual_df.groupby(pd.Grouper(key='harvest_date', freq='MS'))['harvest_weight_kg']
            .sum()
            .reset_index()
        )

        merged = pd.merge(
            pred_df, actual_monthly,
            how='outer',
            left_on='predicted_date', right_on='harvest_date'
        ).fillna(0)

        merged['error'] = abs(merged['predicted_quantity'] - merged['harvest_weight_kg'])
        merged['accuracy_%'] = np.where(
            merged['harvest_weight_kg'] > 0,
            100 * (1 - merged['error'] / merged['harvest_weight_kg']),
            0
        )
        merged['accuracy_%'] = merged['accuracy_%'].clip(lower=0)

        # --- Extra evaluation metrics ---
        if len(merged) > 0:
            mape = mean_absolute_percentage_error(
                merged['harvest_weight_kg'], merged['predicted_quantity']
            )
            rmse = np.sqrt(mean_squared_error(
                merged['harvest_weight_kg'], merged['predicted_quantity']
            ))
            corr = np.corrcoef(
                merged['harvest_weight_kg'], merged['predicted_quantity']
            )[0, 1]
        else:
            mape, rmse, corr = 0.0, 0.0, 0.0

        overall_acc = merged['accuracy_%'].mean()
        summary = {
            "overall_accuracy": round(float(overall_acc), 2),
            "mape": round(float(mape * 100), 2),
            "rmse": round(float(rmse), 2),
            "correlation": round(float(corr), 3),
            "monthly": merged[['predicted_date', 'predicted_quantity', 'harvest_weight_kg', 'accuracy_%']]
            .to_dict(orient='records')
        }

        # --- Save evaluation and plot ---
        if save_path:
            summary = json.loads(json.dumps(summary, default=str))
            with open(f"{save_path}_accuracy.json", "w") as f:
                json.dump(summary, f, indent=2)

            # Plot actual vs predicted
            plt.figure(figsize=(10, 5))
            plt.plot(merged['harvest_date'], merged['harvest_weight_kg'], label='Actual', marker='o')
            plt.plot(merged['predicted_date'], merged['predicted_quantity'], label='Predicted', marker='x')
            plt.title('Actual vs Predicted Harvest Weight')
            plt.xlabel('Date')
            plt.ylabel('Weight (kg)')
            plt.legend()
            plt.grid(True, linestyle='--', alpha=0.6)
            plt.tight_layout()
            plt.savefig(f"{save_path}_forecast_plot.png")
            plt.close()

        return summary

    # --- Handle zero data ---
    if float(series.sum()) == 0.0:
        horizon = pd.date_range(start=series.index[-1] + relativedelta(months=+1), periods=args.steps, freq='MS')
        zeros = pd.Series(0.0, index=horizon)
        result = {"forecast": safe_output(zeros), "evaluation": None}
    else:
        series_nonzero = series[series > 0]
        last_observed = series_nonzero.index.max() if not series_nonzero.empty else series.index.max()
        start_forecast = last_observed + relativedelta(months=+1)
        if args.start_from:
            try:
                override = pd.to_datetime(args.start_from)
                # anchor to the first month after override
                start_forecast = pd.Timestamp(override.year, override.month, 1) + relativedelta(months=+1)
            except Exception:
                pass

        model = SARIMAX(series, order=order, seasonal_order=seasonal_order,
                        enforce_stationarity=False, enforce_invertibility=False)
        fit = model.fit(disp=False)
        fc = fit.get_forecast(steps=args.steps)
        pred = fc.predicted_mean
        pred = pred[pred.index >= start_forecast]
        forecast_output = safe_output(pred)
        accuracy_result = None

        if len(series[series > 0]) >= 6:
            predicted_months = set(pred.index.to_period('M'))
            actual_months = set(df['harvest_date'].dt.to_period('M'))
            overlap = predicted_months & actual_months
            if overlap:
                accuracy_result = evaluate_accuracy(
                    pd.DataFrame({'predicted_date': pred.index, 'predicted_quantity': pred.values}),
                    df[['harvest_date', 'harvest_weight_kg']],
                    save_path=os.path.splitext(args.csv_path)[0]
                )

        result = {"forecast": forecast_output, "evaluation": accuracy_result}

    #  Save JSON to storage/app/predictions/
    csv_path = args.csv_path
    base_name = os.path.basename(csv_path).replace('.csv', '')
    # Resolve relative to the Laravel project root
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    output_dir = os.path.join(base_dir, 'storage', 'app', 'predictions')
    os.makedirs(output_dir, exist_ok=True)
    output_path = os.path.join(output_dir, f'{base_name}_prediction.json')
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(result, f, indent=4)

    #  Print JSON to stdout for Laravel
    print(json.dumps(result))

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(json.dumps({
            "error": str(e),
            "trace": traceback.format_exc()
        }))
        sys.exit(1)