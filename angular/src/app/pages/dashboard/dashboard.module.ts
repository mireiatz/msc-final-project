import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { DashboardRoutingModule } from "./dashboard-routing.module";
import { RouterModule } from "@angular/router";

@NgModule({
  imports: [
    DashboardRoutingModule,
    CommonModule,
    RouterModule,
  ]
})
export class DashboardModule {}
