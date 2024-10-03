import logging
import pandas as pd
import numpy as np
from datetime import datetime

class TimeSeriesEngineeringLayer:

    def __init__(self, data):
        self.data = data

    def create_time_series_features(self, df, column, periods=[1, 7, 14, 30, 90, 365]):
        """
        Create lag and rolling average columns for a given feature, e.g., 'quantity' across specified days.
        """
        # Ensure 'date' is in datetime format, sort it and group it
        if not pd.api.types.is_datetime64_any_dtype(df['date']):
            df['date'] = pd.to_datetime(df['date'], format='%Y-%m-%d')
        df = df.sort_values(by=['product_id', 'date'])
        grouped = df.groupby('product_id')

        # Loop through defined periods of time
        for period in periods:
            # Create lags
            lag_col_name = f'{column}_lag_{period}'
            df[lag_col_name] = grouped[column].shift(period)
            df[lag_col_name] = pd.to_numeric(df[lag_col_name], errors='coerce')

            # Create rolling averages, except for 1 day period
            if period != 1:
                rolling_col_name = f'{column}_rolling_avg_{period}'
                df[rolling_col_name] = grouped[column].transform(lambda x: x.rolling(period, min_periods=1).mean())

        # Clean up the decimal spaces
        df = df.round(4)

        logging.info("Time series features created")

        return df

    def remove_historical_data_records(self, df):
        """
        Remove historical data from the dataframe.
        """
        # Keep only data with no 'quantity', i.e. data for prediction
        df = df[df['quantity'].isna()].copy()

        # Drop the 'quantity' column
        df.drop(columns=['quantity'], inplace=True)

        logging.info(f"Historical data removed")

        return df

    def process_historical_data(self):
        """
        Re-structure a DataFrame with weekly records and create new features.
        """
        logging.info("Starting time series engineering process...")

        df = self.create_time_series_features(self.data, 'quantity', periods=[1, 7, 14, 30, 90, 365])

        return df

    def process_prediction_data(self):
        """
        Re-structure a DataFrame with data for prediction and create new features.
        """
        logging.info("Starting time series engineering process...")

        df = self.create_time_series_features(self.data, 'quantity', periods=[1, 7, 30])
        df = self.remove_historical_data_records(df)

        return df