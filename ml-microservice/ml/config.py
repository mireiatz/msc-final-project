class Config:

    # Save/read data
    HISTORICAL_DATA_RAW = './ml/data/historical/raw'
    HISTORICAL_DATA_PROCESSED = './ml/data/historical/processed'
    HISTORICAL_DATA_BACKUP = './ml/data/historical/backup'
    MAPPINGS = './ml/data/mappings'

    # ML related info
    TARGET = 'quantity'

    LEAST_FEATURES = [
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
        'in_stock', 'per_item_value'
    ]

    MAIN_FEATURES = LONG_PERIOD_FEATURES # To practically set the one used

    MODELS_DIR = './ml/models'

    XGB_MODEL = f'{MODELS_DIR}/new_long_xgboost_model_20241005_193856.pkl'
    LGB_MODEL = f'{MODELS_DIR}/lightgbm_model_20241005_174454.pkl'
    LSTM_MODEL = f'{MODELS_DIR}/lstm_model_20241006_160216.keras'

    MAIN_MODEL = XGB_MODEL  # To practically set the one used

    # Scripts
    SCRIPTS = './scripts'

config = Config()
