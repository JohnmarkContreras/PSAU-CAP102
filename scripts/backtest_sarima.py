# scripts/backtest_sarima.py
import sys, json, pandas as pd, numpy as np
from statsmodels.tsa.statespace.sarimax import SARIMAX
from sklearn.metrics import mean_squared_error, mean_absolute_percentage_error
import warnings

warnings.filterwarnings("ignore")

# Read JSON from stdin
data = json.load(sys.stdin)
df = pd.DataFrame(data)

# Ensure correct dtypes
df['harvest_date'] = pd.to_datetime(df['harvest_date'], errors='coerce')
df['harvest_weight_kg'] = pd.to_numeric(df['harvest_weight_kg'], errors='coerce')
df = df.dropna(subset=['harvest_date', 'harvest_weight_kg'])

if df.empty:
    print(json.dumps({"error": "No valid harvest data"}))
    sys.exit(0)

# Build monthly series
series = (
    df.set_index('harvest_date')['harvest_weight_kg']
      .resample('MS').sum().fillna(0).astype(float)
)

# Train/test split
train_end = '2023-12-01'
train = series[:train_end]
test = series['2024-01-01':]
steps = len(test)

if steps == 0 or train.empty:
    print(json.dumps({"error": "Not enough data to run backtest"}))
    sys.exit(0)

# Fit SARIMA
model = SARIMAX(train, order=(1,1,1), seasonal_order=(1,1,1,12),
                enforce_stationarity=False, enforce_invertibility=False)
fit = model.fit(disp=False)
pred = fit.get_forecast(steps=steps).predicted_mean.clip(lower=0)

# Metrics on non-zero months
# Metrics on non-zero months
non_zero_mask = test > 0
test_non_zero = test[non_zero_mask]
pred_non_zero = pred[non_zero_mask]

rmse = np.sqrt(mean_squared_error(test_non_zero, pred_non_zero)) if len(test_non_zero) else None
mape = mean_absolute_percentage_error(test_non_zero, pred_non_zero)*100 if len(test_non_zero) else None

# Always return full test arrays for display
result = {
    "dates": [d.strftime("%Y-%m") for d in test.index],
    "predicted": pred.round(2).tolist(),
    "actual": test.tolist(),
    "rmse": round(rmse, 2) if rmse is not None else None,
    "mape": round(mape, 2) if mape is not None else None
}
print(json.dumps(result))

