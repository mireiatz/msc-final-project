from ml.modeling.evaluator import Evaluator
from ml.modeling.demand_predictor import DemandPredictor
from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
import logging
import yaml
import pandas as pd

def run(data, model_path, features, target):

    # Step 1: Run predictions
    predictor = DemandPredictor(
        model_path=model_path,
    )
    predictions = predictor.run(data[features])  # Pass only the required features

    # Step 2: Evaluate the predictions
    evaluator = Evaluator(
        target=target
    )
    metrics = evaluator.run(data[target], predictions)

    logging.info(f"Evaluation completed for model {model_path}")
    logging.info(metrics)
