import sys, json, pandas as pd, numpy as np
from statsmodels.tsa.statespace.sarimax import SARIMAX
from sklearn.metrics import mean_squared_error
import warnings
warnings.filterwarnings("ignore")

data = json.load(sys.stdin)
df = pd.DataFrame(data)

df['harvest_date'] = pd.to_datetime(df['harvest_date'], errors='coerce')
df['harvest_weight_kg'] = pd.to_numeric(df['harvest_weight_kg'], errors='coerce')
df = df.dropna(subset=['harvest_date', 'harvest_weight_kg'])

if df.empty:
    print(json.dumps({"error": "No valid harvest data"}))
    sys.exit(0)

series = (
    df.set_index('harvest_date')['harvest_weight_kg']
      .resample('MS').sum().fillna(0).astype(float)
)

# Define cutoff dates for backtesting
cutoffs = ['2022-12-01', '2024-06-01', '2024-12-01']
results = []

for cutoff in cutoffs:
    cutoff_date = pd.to_datetime(cutoff)
    train = series[:cutoff_date]
    test = series[cutoff_date + pd.offsets.MonthBegin():]
    steps = len(test)

    if steps == 0 or train.empty:
        continue

    # Fit SARIMA model
    model = SARIMAX(train, order=(1,1,1), seasonal_order=(1,1,1,12),
                    enforce_stationarity=False, enforce_invertibility=False)
    fit = model.fit(disp=False)

    # Forecast
    pred = fit.get_forecast(steps=steps).predicted_mean.clip(lower=0)

    # Calculate RMSE on all test points
    rmse = np.sqrt(mean_squared_error(test, pred))

    # Calculate MAPE only where actuals > 0 to avoid division by zero
    non_zero_mask = test > 0
    if non_zero_mask.any():
        test_non_zero = test[non_zero_mask]
        pred_non_zero = pred[non_zero_mask]
        mape = (np.abs((test_non_zero - pred_non_zero) / test_non_zero)).mean() * 100
    else:
        mape = None  # No positive actual values, MAPE undefined

    results.append({
        "cutoff": cutoff,
        "dates": [d.strftime("%Y-%m") for d in test.index],
        "predicted": pred.round(2).tolist(),
        "actual": test.round(2).tolist(),
        "rmse": round(rmse, 2),
        "mape": round(mape, 2) if mape is not None else None
    })

print(json.dumps({"backtests": results}))
