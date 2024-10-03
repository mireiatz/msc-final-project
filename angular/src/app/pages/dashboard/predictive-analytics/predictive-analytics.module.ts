import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PredictiveAnalyticsRoutingModule } from "./predictive-analytics-routing.module";
import { PredictiveAnalyticsPage } from "./predictive-analytics.page";
import { SharedModule } from "../../../shared/shared.module";
import { OverviewDemandForecastPage } from "./overview-demand-forecast/overview-demand-forecast.page";
import { LineChartModule, NgxChartsModule } from "@swimlane/ngx-charts";
import { CategoryDemandForecastPage } from "./category-demand-forecast/category-demand-forecast.page";

import {
  ProductDemandForecastModalComponent
} from "./modals/product-demand-forecast-modal/product-demand-forecast-modal.component";

const MODALS = [
  ProductDemandForecastModalComponent,
]

const PAGES = [
  PredictiveAnalyticsPage,
  OverviewDemandForecastPage,
  CategoryDemandForecastPage,
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
