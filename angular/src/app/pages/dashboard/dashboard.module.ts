import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { DashboardRoutingModule } from "./dashboard-routing.module";
import { RouterModule } from "@angular/router";
import { SharedModule } from "../../shared/shared.module";

@NgModule({
  imports: [
    DashboardRoutingModule,
    CommonModule,
    RouterModule,
    SharedModule,
  ]
})
export class DashboardModule {}
