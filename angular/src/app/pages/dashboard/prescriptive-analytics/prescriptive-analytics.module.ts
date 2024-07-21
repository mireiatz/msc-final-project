import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PrescriptiveAnalyticsRoutingModule } from "./prescriptive-analytics-routing.module";

@NgModule({
  imports: [
    PrescriptiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
  ]
})
export class PrescriptiveAnalyticsModule {}
