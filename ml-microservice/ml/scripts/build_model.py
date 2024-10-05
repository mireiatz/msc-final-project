import subprocess
import os
import argparse
import logging
from ml.config import config
from ml.scripts.train_model import main as train_main
from ml.scripts.evaluate_model import main as evaluate_main

def main(args):
    try:
        # Trigger the imported main function in the training script
        model_path, X_test, y_test = train_main(args.data_path, args.model_type, args.output_path)

        logging.info(f"Training completed. Model saved at: {model_path}")

        # Trigger the imported main function in the evaluation script
        evaluate_main(model_path, X_test, y_test)

        logging.info(f"Model evaluation completed")

    except Exception as e:
        logging.error(f"Error in model build pipeline: {str(e)}")
        raise

if __name__ == "__main__":
    logging.info("Starting model build pipeline script...")

    # Get the arguments
    parser = argparse.ArgumentParser(description='Build (train and evaluate) a model')
    parser.add_argument('--data_path', required=False, help='Path to the preprocessed data file')
    parser.add_argument('--output_path', required=False, help='Path to save the trained model')
    parser.add_argument('--model_type', required=True, help='Type of the model to be trained: (XGBoost) or ligthgbm (LightGBM)')

    args = parser.parse_args()

    main(args)
