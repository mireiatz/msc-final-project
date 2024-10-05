from ml.preprocessing.data_splitting_layer import DataSplittingLayer
from ml.modeling.trainer import Trainer
from ml.config import config
import pandas as pd
import argparse
import logging
import os

def main(data_path, model_type, output_path):

    try:
        # Load the preprocessed data from a provided or the default path
        data_path = data_path or os.path.join(config.HISTORICAL_DATA_PROCESSED, 'processed_data.csv')
        df = pd.read_csv(data_path)
        logging.info(f"Data loaded from {data_path}")
    except Exception as e:
        logging.error(f"Error loading data: {e}")
        return

    # Split the data using the preprocessing Splitting Layer
    try:
        splitter = DataSplittingLayer(df)
        X_train, y_train, X_test, y_test = splitter.split_timeline_in_two_halves()
        logging.info("Data splitting completed")
    except Exception as e:
        logging.error(f"Error during data splitting: {e}")
        return

    try:
        # Initialise the trainer and train the model
        trainer = Trainer(model_type=model_type, output_path=output_path)
        best_model, model_path = trainer.run(X_train, y_train)
        logging.info(f"Model training complete for {model_type}")
    except Exception as e:
        logging.error(f"Error during model training: {e}")
        return

    logging.info(f"Model training completed for {model_type}")

    # Return the model path and data needed for evaluation
    return model_path, X_test, y_test

if __name__ == "__main__":
    logging.info("Starting model training script...")

    # Get the arguments
    parser = argparse.ArgumentParser(description='Train a new model')
    parser.add_argument('--data_path', required=False, help='Path to the preprocessed data CSV file')
    parser.add_argument('--output_path', required=False, help='Path to save the trained model')
    parser.add_argument('--model_type', required=True, help='Type of the model to be trained: xgboost (XGBoost) or ligthgbm (LightGBM)')

    args = parser.parse_args()

    model_path, X_test, y_test = main(args.data_path, args.model_type, args.output_path)
