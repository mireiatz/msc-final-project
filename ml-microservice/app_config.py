class AppConfig:

    # Scripts
    SCRIPTS = 'ml/scripts'

    # Available ML models, prioritised
    MAIN_MODEL = 'ml/models/xgboost_demand_forecast_model_20240928_134943.pkl'
    SECOND_MODEL = 'ml/models/lightgbm_demand_forecast_model_20240928_134943.pkl'

app_config = AppConfig()
