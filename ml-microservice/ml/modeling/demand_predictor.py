from ml.config import config
import pandas as pd
import joblib
import logging

class DemandPredictor:

    def __init__(self, model_path=None):
        self.model_path = model_path or config.MAIN_XGB_MODEL
        self.model = None  # To save the model when it's loaded

    def load_model(self):
        """
        Load the saved model.
        """
        try:
            self.model = joblib.load(self.model_path)

            logging.info(f"Model loaded")
        except Exception as e:
            logging.error(f"Error loading the model: {str(e)}")
            raise RuntimeError(f"Failed to load model. Error: {str(e)}")


    def make_predictions(self, data):
        """
        Make predictions using the loaded model.
        """
        try:
            predictions = self.model.predict(data)

            logging.info(f"Predictions made")

            return predictions
        except Exception as e:
            logging.error(f"Error during prediction: {str(e)}")
            raise RuntimeError(f"Prediction process failed. Error: {str(e)}")

    def transform_predictions(self, predictions, source_product_ids, dates):
        """
        Transform predictions to a structure and format readily-usable for demand forecast.
        """
        # Create a DataFrame
        predictions_df = pd.DataFrame({
            'product_id': source_product_ids,
            'date': dates,
            'value': predictions
        })

        # Format the date
        predictions_df['date'] = pd.to_datetime(predictions_df['date'], unit='ms').dt.strftime('%Y-%m-%d')

        # Round to nearest integer and convert negatives to 0
        predictions_df['value'] = predictions_df['value'].round(0).clip(lower=0).astype(int)

        logging.info(f"Predictions transformed")

        return predictions_df

    def run(self, data, source_product_ids, dates):
        """
        Load the model, make predictions, and post-process the results.
        """
        logging.info(f"Predictor running...")

        # Check data before making predictions
        if data is None:
            logging.error("No data provided for predictions")
            raise ValueError("No data provided for predictions")

        if not isinstance(data, pd.DataFrame):
            logging.error(f"Expected data as a DataFrame, got {type(data)}")
            raise ValueError(f"Expected data as a DataFrame, got {type(data)}")

        if data.empty:
            logging.error("Empty DataFrame provided for predictions")
            raise ValueError("Empty DataFrame provided for predictions")

        # Load the model
        self.load_model()

        # Make predictions
        predictions = self.make_predictions(data)

        # Prepare results
        predictions_df = self.transform_predictions(predictions, source_product_ids, dates)

        logging.info("Demand prediction completed")

        return predictions_df
