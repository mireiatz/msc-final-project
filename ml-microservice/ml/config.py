class Config:

    # Save/read data
    HISTORICAL_DATA_RAW = './ml/data/historical/raw'
    HISTORICAL_DATA_PROCESSED = './ml/data/historical/processed'
    HISTORICAL_DATA_BACKUP = './ml/data/historical/backup'
    MAPPINGS = './ml/data/mappings'

    # Available ML models, and related info
    MAIN_XGB_MODEL = './ml/models/xgboost_demand_forecast_model_20240928_134943.pkl'
    MAIN_LGB_MODEL = './ml/models/lightgbm_demand_forecast_model_20240928_134943.pkl'
    MAIN_FEATURES = [
        'product_id_encoded', 'category_encoded', 'quantity_lag_1',
        'quantity_lag_7', 'quantity_rolling_avg_7', 'quantity_rolling_avg_30',
        'month_cos', 'month_sin', 'weekday_cos', 'weekday_sin',
        'in_stock', 'per_item_value'
    ]

config = Config()
