import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PredictiveAnalyticsRoutingModule } from "./predictive-analytics-routing.module";

@NgModule({
  imports: [
    PredictiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
  ]
})
export class PredictiveAnalyticsModule {}
