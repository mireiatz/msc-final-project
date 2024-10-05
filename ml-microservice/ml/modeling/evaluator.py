import pandas as pd
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import numpy as np
import logging

class Evaluator:

    def __init__(self, target):
        self.target = target

    def calculate_metrics(self, y_test, predictions):
        """
        Calculate standard regression metrics.
        """
        mae = mean_absolute_error(y_test, predictions)
        mse = mean_squared_error(y_test, predictions)
        rmse = np.sqrt(mse)
        r2 = r2_score(y_test, predictions)

        return mae, rmse, r2

    def run(self, y_test, predictions):
        """
        Run the evaluation.
        """
        logging.info("Evaluating predictions...")

        pd.set_option('display.float_format', '{:.4f}'.format)  # Cap the decimals

        # Calculate base metrics
        mae, rmse, r2 = self.calculate_metrics(y_test, predictions)

        # Return all the metrics
        return {
            'mae': mae,
            'rmse': rmse,
            'r2': r2,
        }