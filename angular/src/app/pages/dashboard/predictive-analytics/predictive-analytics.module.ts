import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PredictiveAnalyticsRoutingModule } from "./predictive-analytics-routing.module";
import { PredictiveAnalyticsPage } from "./predictive-analytics.page";
import { SharedModule } from "../../../shared/shared.module";
import { DemandForecastPage } from "./demand-forecast/demand-forecast.page";

const PAGES = [
  PredictiveAnalyticsPage,
  DemandForecastPage,
];

@NgModule({
  declarations: [
    ...PAGES,
  ],
  imports: [
    PredictiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
    SharedModule,
  ]
})
export class PredictiveAnalyticsModule {}
