# AI in Small- and Medium-Sized Supermarkets: Machine Learning-Driven Data Analytics for Inventory Management

## Overview
This project is focused on developing a Machine Learning (ML)-driven data analytics tool for Small- and Medium-Sized (SME) supermarkets to optimise their Inventory Management (IM) operations. The tool is built to address the inefficiencies of manual inventory processes, making it more efficient, data-driven, and scalable. It features a dashboard with descriptive, predictive and prescriptive analytics, allowing users to monitor current inventory, forecast future demand, and receive reorder suggestions.

***

## Key Features
* **Descriptive Analytics:** insights into inventory, sales, and product performance through metrics and graphic visualisations.
* **Predictive Analytics:** forecasts of future demand based on historical data and ML  models.
* **Prescriptive Analytics:** recommendations for optimal reorder quantities based on ML predictions.

***

## Technologies
* **Frontend:** Angular (with ngx-charts for data visualisation).
* **Backend:** Laravel (RESTful API).
* **ML:** Flask and Python.
* **Database:** MySQL.
* **Containerisation:** Docker for deployment and scalability.

***

## System Requirements
* **PHP:** v8.2
* **Laravel:** v11.15
* **Node.js:** v21.1
* **Angular:** v18.1
* **MySQL:** v5.7
* **Docker:** v27.1
* **Python:** 3.12

***

## Installation

#### Step 1: Clone the Repository

```
https://github.com/University-of-London/project-module-2024-apr-mireiatz.git
```

#### Step 2: Docker Setup

* Build the containers with Docker:
```
docker-compose up -d
```

#### Step 3: Backend setup (Laravel)

* Navigate to the backend directory:
```
cd laravel
```

* Install Laravel dependencies:
```
composer install
```

* Copy the .env file and configure your MySQL database settings:
```
cp .env.example .env
```

* Enter the container:
``` 
docker compose exec laravel bash
```

* Run migrations to set up the database:
``` 
php artisan migrate
```

#### Step 4: Frontend Setup (Angular)

* Navigate to the frontend directory:
```
cd ../frontend
```

* Install dependencies:
```
npm install
```

#### Step 5: ML Microservice (Python)

* Navigate to the ML microservice directory:
``` 
cd ../ml
```

* Install the required packages:
``` 
pip3 install -r requirements.txt
```

## Running the application

* Build the containers with Docker:
```
docker-compose up -d
```

* Navigate to the angular directory:
``` 
cd ../angular
```

* Generate the application bundle:
``` 
npm run api:gen
npm run watch
```

***

## Testing

#### Backend (Laravel)
* **Unit Testing**
``` 
php artisan test
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

### 3. Reordering
This is the `Prescriptive Analytics` section.

