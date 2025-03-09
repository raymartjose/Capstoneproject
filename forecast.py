import sys
import json
import base64
import pandas as pd
from prophet import Prophet
import logging

# Disable unwanted logs
logging.getLogger("cmdstanpy").disabled = True

try:
    # Read input data from PHP
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No input data provided"}))
        sys.exit(1)

    input_data = json.loads(base64.b64decode(sys.argv[1]).decode("utf-8"))

    if not input_data:
        print(json.dumps({"error": "Empty input data"}))
        sys.exit(1)

    df = pd.DataFrame(input_data)

    if not all(col in df.columns for col in ["month", "income", "expenses"]):
        print(json.dumps({"error": "Missing required columns"}))
        sys.exit(1)

    df_income = df[["month", "income"]].rename(columns={"month": "ds", "income": "y"})
    df_expenses = df[["month", "expenses"]].rename(columns={"month": "ds", "expenses": "y"})

    df_income["ds"] = pd.to_datetime(df_income["ds"])
    df_expenses["ds"] = pd.to_datetime(df_expenses["ds"])

    # Prophet Model with changepoint detection
    prophet_income = Prophet(changepoint_prior_scale=0.05)
    prophet_expenses = Prophet(changepoint_prior_scale=0.05)

    prophet_income.fit(df_income)
    prophet_expenses.fit(df_expenses)

    future_income = prophet_income.make_future_dataframe(periods=6, freq='ME')
    future_expenses = prophet_expenses.make_future_dataframe(periods=6, freq='ME')

    forecast_income = prophet_income.predict(future_income)
    forecast_expenses = prophet_expenses.predict(future_expenses)

    forecast_months = forecast_income.tail(6)[["ds", "yhat"]].rename(columns={"yhat": "income"})
    forecast_months["expenses"] = forecast_expenses.tail(6)["yhat"]
    forecast_months["is_forecast"] = True  

    forecast_months["ds"] = forecast_months["ds"].dt.strftime('%Y-%m')
    forecast_months.rename(columns={"ds": "month"}, inplace=True)

    # Detect anomalies (30% drop in cash flow)
    anomalies = []
    previous_cash_flow = None

    for index, row in forecast_months.iterrows():
        cash_flow = row["income"] - row["expenses"]
        if previous_cash_flow is not None and cash_flow < previous_cash_flow * 0.7:
            anomalies.append({"month": row["month"], "anomaly": True})
        else:
            anomalies.append({"month": row["month"], "anomaly": False})

        previous_cash_flow = cash_flow

    # Merge anomaly detection results
    forecast_months["anomaly"] = [entry["anomaly"] for entry in anomalies]

    print(json.dumps(forecast_months.to_dict(orient="records")))

except Exception as e:
    print(json.dumps({"error": str(e)}))
    sys.exit(1)
