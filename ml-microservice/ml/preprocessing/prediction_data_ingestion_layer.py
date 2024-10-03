import logging
import os
import pandas as pd

class PredictionDataIngestionLayer:

    def __init__(self, data):
        self.data = data

    def process(self):
        """
        Process the prediction data using a dictionary of lists to create a DataFrame.
        """
        logging.info("Starting ingestion of prediction data process...")

        # Prepare the dictionaries
        prediction_data = {
            'product_name': [],
            'category': [],
            'per_item_value': [],
            'in_stock': [],
            'date': []
        }

        historical_data = {
            'product_name': [],
            'category': [],
            'per_item_value': [],
            'in_stock': [],
            'date': [],
            'quantity': []  # Historical sales include quantity
        }

        # Extract prediction dates
        prediction_dates = self.data['prediction_dates']

        # Loop through each product
        products = self.data['products']
        for product in products:
            # Extract product details and historical sales for each product
            product_info = product['details']
            historical_sales = product['historical_sales']

            # Collect historical sales data for each product
            for sale in historical_sales:
                historical_data['product_name'].append(product_info['product_name'])
                historical_data['category'].append(product_info['category'])
                historical_data['per_item_value'].append(product_info['per_item_value'])
                historical_data['in_stock'].append(product_info['in_stock'])
                historical_data['date'].append(sale['date'])  # Historical sales date
                historical_data['quantity'].append(sale['quantity'])  # Historical sales quantity

            # Collect prediction data for each product using the prediction dates
            for date in prediction_dates:
                prediction_data['product_name'].append(product_info['product_name'])
                prediction_data['category'].append(product_info['category'])
                prediction_data['per_item_value'].append(product_info['per_item_value'])
                prediction_data['in_stock'].append(product_info['in_stock'])
                prediction_data['date'].append(date)  # Future prediction date

        # Convert dictionaries to DataFrames
        df_prediction = pd.DataFrame(prediction_data)
        df_historical = pd.DataFrame(historical_data)

        # Combine both DataFrames
        df = pd.concat([df_historical, df_prediction], ignore_index=True)

        logging.info("Ingestion completed")

        return df
