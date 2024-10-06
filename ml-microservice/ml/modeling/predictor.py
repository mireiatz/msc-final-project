from ml.config import config
from tensorflow.keras.models import load_model
import pandas as pd
import numpy as np
import joblib
import logging
import os

class Predictor:

    def __init__(self, model_path=None):
        self.model_path = model_path or config.MAIN_MODEL
        self.model = None  # To save the model when it's loaded
        self.is_lstm = False  # Flag for conditional processing

    def load_model(self):
        """
        Load the saved model, Keras or scikit-learn based.
        """
        try:
            if self.model_path.endswith('.keras') or os.path.isdir(self.model_path):  # Load Keras model (LSTM)
                self.is_lstm = True # Flag as keras
                self.model = load_model(self.model_path)

                logging.info(f"Keras model loaded from {self.model_path}")

            elif self.model_path.endswith('.pkl'): # Load scikit-learn-based model (XGBoost or LightGBM)
                self.model = joblib.load(self.model_path)

                logging.info(f"scikit-learn-based model loaded from {self.model_path}")
            else:
                raise ValueError(f"Unsupported model file format: {self.model_path}")

            logging.info(f"Model loaded")
        except Exception as e:
            logging.error(f"Error loading the model: {str(e)}")
            raise RuntimeError(f"Failed to load model. Error: {str(e)}")

    def prepare_input_for_lstm(self, data, sequence_length=60):
        """
        Prepare the input to ensure it has the shape expected by the LSTM model
        (batch_size, sequence_length, num_features).
        """
        if len(data.shape) == 2:
            # If input is missing the time dimension, add it and repeat for 'sequence_length'
            data = np.expand_dims(data, axis=1)  # Add a time dimension
            data = np.tile(data, (1, sequence_length, 1))  # Repeat for sequence length

        return data

    def make_predictions(self, data):
        """
        Make predictions using the loaded model.
        """
        try:
            if self.is_lstm:
                # Prepare input for LSTM model
                data = self.prepare_input_for_lstm(data)

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
        if self.is_lstm:
            # Flatten the predictions array if it's 2D
            predictions = predictions.flatten()

        # Filter the predictions to keep only numeric values
        valid_predictions = predictions[np.isfinite(predictions)]

        # Create a DataFrame
        predictions_df = pd.DataFrame({
            'product_id': source_product_ids,
            'date': dates,
            'value': predictions
        })

        # Format the date
        predictions_df['date'] = pd.to_datetime(predictions_df['date']).dt.strftime('%Y-%m-%d')

        # Round to nearest integer and convert negatives to 0
        predictions_df['value'] = predictions_df['value'].round(0).clip(lower=0).astype(int)

        logging.info(f"Predictions transformed")

        return predictions_df

    def sanity_check(self, data):
        """
        Check the data before starting the process.
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

    def run_live_predictions(self, data, source_product_ids, dates):
        """
        Make live predictions using product details and dates.
        """
        logging.info("Running live predictions...")

        # Check the data for any issues
        self.sanity_check(data)

        # Load the model
        self.load_model()

        # Make predictions
        predictions = self.make_predictions(data)

        # Prepare results and return them
        predictions_df = self.transform_predictions(predictions, source_product_ids, dates)

        return predictions_df

    def run_predictions_for_evaluation(self, X_test):
        """
        Make predictions for evaluation purposes.
        """
        logging.info("Running predictions for evaluation...")

        # Check the data for any issues
        self.sanity_check(X_test)

        # Load the model
        self.load_model()

        # Make predictions and return them
        predictions = self.make_predictions(X_test)

        return predictions
