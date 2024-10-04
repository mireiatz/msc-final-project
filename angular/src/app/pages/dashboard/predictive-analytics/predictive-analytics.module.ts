import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PredictiveAnalyticsRoutingModule } from "./predictive-analytics-routing.module";
import { PredictiveAnalyticsPage } from "./predictive-analytics.page";
import { SharedModule } from "../../../shared/shared.module";
import { CategoryLevelDemandForecastPage } from "./category-level-demand-forecast/category-level-demand-forecast.page";
import { LineChartModule, NgxChartsModule } from "@swimlane/ngx-charts";
import { ProductLevelDemandForecastPage } from "./product-level-demand-forecast/product-level-demand-forecast.page";

import {
  ProductDemandForecastModalComponent
} from "./modals/product-demand-forecast-modal/product-demand-forecast-modal.component";
import { MonthDemandForecastPage } from "./month-demand-forecast/month-demand-forecast.page";
import { WeeklyDemandForecastPage } from "./weekly-demand-forecast/weekly-demand-forecast.page";

const MODALS = [
  ProductDemandForecastModalComponent,
]

const PAGES = [
  PredictiveAnalyticsPage,
  CategoryLevelDemandForecastPage,
  ProductLevelDemandForecastPage,
  MonthDemandForecastPage,
  WeeklyDemandForecastPage,
];

@NgModule({
  declarations: [
    ...PAGES,
    ...MODALS,
  ],
  imports: [
    PredictiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
    SharedModule,
    LineChartModule,
    NgxChartsModule,
  ]
})
export class PredictiveAnalyticsModule {}
