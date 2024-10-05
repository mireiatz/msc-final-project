from ml.config import config
import pandas as pd
import logging

class DataSplittingLayer:

    def __init__(self, data, features=None, target=None):
        self.data = data
        self.features = features or config.MAIN_FEATURES
        self.target = target or config.TARGET

    def sanity_check(self, df):
        """
        Ensure 'date' is in datetime format and sort it.
        """
        df['date'] = pd.to_datetime(df['date'])

        return df.sort_values(by='date')

    def time_based_split(self, split_date):
        """
        Perform a time-based split on the dataset, based on a specific date.
        """
        # Check data
        df = self.sanity_check(self.data)

        # Split data
        train_data = df[df['date'] <= split_date]
        test_data = df[df['date'] > split_date]

        # Define and return the splits
        X_train = train_data[self.features]
        y_train = train_data[self.target]
        X_test = test_data[self.features]
        y_test = test_data[self.target]

        logging.info("Date based split completed")

        return X_train, y_train, X_test, y_test

    def split_timeline_in_two_halves(self):
        """
        Split the data down the middle of the timeline.
        """
        # Check data
        df = self.sanity_check(self.data)

        # Find the middle index
        mid_index = len(df) // 2

        # Split the data into two halves
        train_data = df.iloc[:mid_index]
        test_data = df.iloc[mid_index:]

        # Define and return the splits
        X_train = train_data[self.features]
        y_train = train_data[self.target]
        X_test = test_data[self.features]
        y_test = test_data[self.target]

        logging.info("Timeline split in two halves completed")

        return X_train, y_train, X_test, y_test