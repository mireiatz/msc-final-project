import pandas as pd
import joblib
import logging

class Predictor:

    def __init__(self, model_path):
        self.model_path = model_path
        self.model = None  # To save the model when it's loaded

    def load_model(self):
        """
        Load the saved model.
        """
        self.model = joblib.load(self.model_path)

    def make_predictions(self, data):
        """
        Make predictions using the loaded model.
        """
        return self.model.predict(data)

    def run(self, data):
        """
        Load the model and make predictions based on the provided data.
        """
        logging.info("Making predictions...")

        self.load_model()

        return self.make_predictions(data)
