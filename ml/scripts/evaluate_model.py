from ..modeling.evaluator import Evaluator
from ..modeling.predictor import Predictor
from ..preprocessing.preprocessing_pipeline import PreprocessingPipeline
from ..logging_config import setup_logging
import yaml
import pandas as pd

# Load configurations for the selected task
feature_set = [
    'product_id_encoded', 'category_encoded',
    'quantity_lag_1', 'quantity_lag_7',
    'quantity_rolling_avg_7', 'quantity_rolling_avg_30',
    'month_cos', 'month_sin', 'weekday_cos', 'weekday_sin',
    'in_stock', 'per_item_value',
]
target = 'quantity'  # Get the target

# Paths for data, model, and processed output
data_path = './ml/data/evaluation/raw/'
output_path = './ml/data/evaluation/processed'
model_path = './ml/models/xgboost_demand_forecast_model_20240928_134943.pkl'

# Step 1: Preprocess the data
pipeline = PreprocessingPipeline(
    data_path=data_path,
    output_path=output_path
)
preprocessed_data = pipeline.run()

# Step 2: Run predictions
predictor = Predictor(
    model_path=model_path,
)
predictions = predictor.run(preprocessed_data[feature_set])  # Pass only the required features

# Step 3: Evaluate the predictions
evaluator = Evaluator(
    target=target
)
metrics = evaluator.run(preprocessed_data[target], predictions)
print("Evaluation Metrics for XGB:", metrics)

model_path = './ml/models/lightgbm_demand_forecast_model_20240928_134943.pkl'

# Step 1: Preprocess the data
pipeline = PreprocessingPipeline(
    data_path=data_path,
    output_path=output_path
)
preprocessed_data = pipeline.run()

# Step 2: Run predictions
predictor = Predictor(
    model_path=model_path,
)
predictions = predictor.run(preprocessed_data[feature_set])

# Step 3: Evaluate the predictions
evaluator = Evaluator(
    target=target
)
metrics = evaluator.run(preprocessed_data[target], predictions)
print("Evaluation Metrics for LGB:", metrics)