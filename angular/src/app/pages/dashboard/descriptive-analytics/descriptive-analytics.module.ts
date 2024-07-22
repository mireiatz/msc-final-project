import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { DescriptiveAnalyticsRoutingModule } from "./descriptive-analytics-routing.module";
import { RouterModule } from "@angular/router";

@NgModule({
  imports: [
    DescriptiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
  ]
})
export class DescriptiveAnalyticsModule {}
