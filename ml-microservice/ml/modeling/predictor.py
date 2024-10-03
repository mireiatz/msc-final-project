import pandas as pd
import joblib
import logging

class Predictor:

    def __init__(self, model_path):
        self.model_path = model_path or app_config.MAIN_XGB_MODEL,

        self.model = None  # To save the model when it's loaded

    def load_model(self):
        """
        Load the saved model.
        """
        try:
            self.model = joblib.load(self.model_path)
        except Exception as e:
            logging.error(f"Error loading the model: {str(e)}")
            raise RuntimeError(f"Failed to load model. Error: {str(e)}")


    def make_predictions(self, data):
        """
        Make predictions using the loaded model.
        """
        try:
            return self.model.predict(data)
        except Exception as e:
            logging.error(f"Error during prediction: {str(e)}")
            raise RuntimeError(f"Prediction process failed. Error: {str(e)}")

    def run(self, data):
        """
        Load the model and make predictions based on the provided data.
        """
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
        return self.make_predictions(data)
