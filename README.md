# AI in Small- and Medium-Sized Supermarkets: Machine Learning-Driven Data Analytics for Inventory Management

## Overview
This project is focused on developing a Machine Learning (ML)-driven data analytics tool for Small- and Medium-Sized (SME) supermarkets to optimise their Inventory Management (IM) operations. The tool is built to address the inefficiencies of manual inventory processes, making it more efficient, data-driven, and scalable. It features a dashboard with descriptive, predictive and prescriptive analytics, allowing users to monitor current inventory, forecast future demand, and receive reorder suggestions.

***

## Key Features
* **Descriptive Analytics:** insights into inventory, sales, and product performance through metrics and graphic visualisations.
* **Predictive Analytics:** forecasts of future demand based on historical data and ML  models.
* **Prescriptive Analytics:** recommendations for optimal reorder quantities based on ML predictions.

***

## Main Technologies
* **Frontend:** Angular (with ngx-charts for data visualisation).
* **Backend:** Laravel (RESTful API).
* **ML:** Flask and Python.
* **Database:** MySQL.
* **Containerisation:** Docker for deployment and scalability.

***

## System Requirements
* **Docker**
* **PHP**
* **Laravel**
* **Node.js**
* **Angular**
* **MySQL**
* **Python**
* **Flask**

***

## Installation

#### Step 1: Clone the Repository

```
https://github.com/University-of-London/project-module-2024-apr-mireiatz.git
```

#### Step 2: Start Docker Containers

* Build the containers with Docker:
```
docker-compose up -d
```

#### Step 3: Access the Application

* The Angular frontend will be accessible at `http://localhost:4200`
* The Laravel backend will be accessible at `http://localhost:80`
* The ML microservice will run at `http://localhost:5002`

*** 

## Testing

#### Backend (Laravel) Unit Testing
``` 
cd laravel
docker compose exec laravel bash
php artisan test
```

#### ML microservice (Python) Unit Testing
``` 
cd ml-microservice/ml
pytest
```

***

## Usage
Once the application is running, the dashboard will be displayed with the following sections to which the user can intuitively navigate through the sidebar buttons:

### 1. Current metrics: 
This is the `Descriptive Analytics` section. It contains 4 tabs: `Overview`, `Product Performance`, `Sales` and `Stock`.

#### 1.1. Overview
It provides a quick snapshot of key metrics on stock levels, sales and product performance. The metrics for sales and product performance can be adjusted by determining the date range using the buttons `Day`, `Week` and `Year`, and the date-pickers `Start Date` and `End Date`.

#### 1.2. Product Performance
It provides a paginated list of all products with their main details such as the category and provider, and relevant sales and stock metrics for a given date range. These metrics can be adjusted as previously mentioned.

#### 1.3. Sales 
It displays graphics informing on sales metrics for a given date range, including total sales, sales per category and sales per product. These metrics can also be adjusted like previously indicated.

#### 1.4. Stock Levels
It displays graphics informing on stock levels for products organised by category.

### 2. Demand Forecast
This is the `Predictive Analytics` section.

#### 2.1. Category-level
It displays a comparative graph with the demand predictions for the next 30 days, aggregated by category.

#### 2.2. Product-level
It displays a comparative graph with the demand predictions for each product within a category, selectable through a dropdown.

#### 2.3. Weekly
It displays the aggregated predictions per week for the next couple of weeks in a bar chart.

#### 2.4. Month
It displays the aggregated predictions for the next 30 days per category in a comparative bar chart.

### 3. Reordering
This is the `Prescriptive Analytics` section. It features a table with the reorder suggestions for products per provider and category, selectable through dropdowns.

