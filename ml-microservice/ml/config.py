class Config:

    # Save/read data
    HISTORICAL_DATA_RAW = './ml/data/historical/raw'
    HISTORICAL_DATA_PROCESSED = './ml/data/historical/processed'
    HISTORICAL_DATA_BACKUP = './ml/data/historical/backup'
    MAPPINGS = './ml/data/mappings'

    # Available ML models, and related info
    TARGET = 'quantity'

    MAIN_FEATURES = [
        'product_id_encoded', 'category_encoded', 'quantity_lag_1',
        'quantity_lag_7', 'quantity_rolling_avg_7', 'quantity_rolling_avg_30',
        'month_cos', 'month_sin', 'weekday_cos', 'weekday_sin',
        'in_stock', 'per_item_value'
    ]

    LONG_PERIOD_FEATURES = [
        'product_id_encoded', 'category_encoded',
        'quantity_lag_1', 'quantity_lag_7', 'quantity_lag_30', 'quantity_lag_90', 'quantity_lag_365',
        'quantity_rolling_avg_7', 'quantity_rolling_avg_30', 'quantity_rolling_avg_90', 'quantity_rolling_avg_365',
        'month_sin', 'month_cos', 'weekday_sin', 'weekday_cos',
        'in_stock', 'per_item_value',
    ]

    MODELS = './ml/models'
    MAIN_XGB_MODEL = './ml/models/xgboost_model_20241005_174454.pkl'
    MAIN_LGB_MODEL = './ml/models/lightgbm_model_20241005_174454.pkl'

    # Scripts
    SCRIPTS = './scripts'

config = Config()
