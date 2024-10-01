import pandas as pd
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import numpy as np
import logging

class Evaluator:

    def __init__(self, target):
        self.target = target

    def calculate_metrics(self, true_values, predictions):
        """
        Calculate standard regression metrics.
        """
        mae = mean_absolute_error(true_values, predictions)
        mse = mean_squared_error(true_values, predictions)
        rmse = np.sqrt(mse)
        r2 = r2_score(true_values, predictions)

        return mae, mse, rmse, r2

    def run(self, true_values, predictions):
        """
        Run the evaluation.
        """
        logging.info("Evaluating predictions...")

        pd.set_option('display.float_format', '{:.4f}'.format)

        # Calculate base metrics
        mae, mse, rmse, r2 = self.calculate_metrics(true_values, predictions)

        # Return all the metrics
        return {
            'mae': mae,
            'mse': mse,
            'rmse': rmse,
            'r2': r2,
        }