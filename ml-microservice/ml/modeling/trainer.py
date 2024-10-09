from ml.config import config
from sklearn.model_selection import RandomizedSearchCV, TimeSeriesSplit
from datetime import datetime
import lightgbm as lgb
import xgboost as xgb
import joblib
import logging
import os

class Trainer:

    def __init__(self, model_type='xgboost', output_path=None):
        """
        Initialise the trainer with a specific model, XGBoost or LightGBM.
        """
        self.model_type = model_type
        self.output_path = output_path or config.MODELS
        self.model = None

    def save_model(self, model):
        """
        Save the model with a timestamp as version control.
        """
        # Create a filename and set the model path
        version = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f'{self.model_type}_model_{version}.pkl'
        model_path = os.path.join(self.output_path, filename)

        # Save the model
        joblib.dump(model, model_path)

        logging.info(f"Model saved as {model_path}")

        return model_path

    def default_params(self):
        """
        Return default parameters for the chosen model.
        """
        if self.model_type == 'xgboost':
            return {
                'n_estimators': [100, 200, 300, 400],
                'learning_rate': [0.01, 0.05, 0.1, 0.3],
                'max_depth': [3, 5, 7, 10],
                'subsample': [0.7, 0.8, 0.9, 1.0],
                'colsample_bytree': [0.7, 0.8, 0.9, 1.0],
                'reg_alpha': [0, 0.1, 0.5, 1.0],
                'reg_lambda': [0, 0.1, 0.5, 1.0],
                'gamma': [0, 0.1, 0.3, 0.5],
                'min_child_weight': [1, 3, 5, 7],
            }
        elif self.model_type == 'lightgbm':
            return {
                'n_estimators': [100, 200, 300, 400],
                'learning_rate': [0.01, 0.05, 0.1, 0.3],
                'max_depth': [3, 5, 7, 10],
                'subsample': [0.7, 0.8, 0.9, 1.0],
                'colsample_bytree': [0.7, 0.8, 0.9, 1.0],
                'reg_alpha': [0, 0.1, 0.5, 1.0],
                'reg_lambda': [0, 0.1, 0.5, 1.0],
                'num_leaves': [31, 61],
                'min_child_samples': [50, 100],
            }

    def random_search_train_model(self, X_train, y_train, tscv, n_iter=50):
        """
        Train the model using randomised search for hyperparameter tuning.
        """
        # Get the params
        model_params = self.default_params()

        # Initialise the models
        if self.model_type == 'xgboost':
            self.model = xgb.XGBRegressor()
        elif self.model_type == 'lightgbm':
            self.model = lgb.LGBMRegressor()

        # Run randomised search
        search = RandomizedSearchCV(
            estimator=self.model,
            param_distributions=model_params,
            n_iter=n_iter,
            scoring='neg_mean_absolute_error',
            cv=tscv,
            verbose=1,
            random_state=42,
            n_jobs=-1
        )

        # Fit the model
        search.fit(X_train, y_train)

        logging.info(f"Best parameters found: {search.best_params_}")

        return search.best_estimator_

    def setup_time_series_split(self, X_train, n_splits=5):
        """
        Set up a TimeSeriesSplit for cross-validation.
        """
        # Dynamically adjust splits based on training data size
        n_splits = min(n_splits, len(X_train) - 1)

        return TimeSeriesSplit(n_splits=n_splits)

    def run(self, X_train, y_train):
        """
        Main method to train the model and save the best version.
        """
        # Implement time series split
        tscv = self.setup_time_series_split(X_train)

        # Find the best model
        best_model = self.random_search_train_model(X_train, y_train, tscv)

        # Save the model and return it with its path
        model_path = self.save_model(best_model)

        return best_model, model_path
