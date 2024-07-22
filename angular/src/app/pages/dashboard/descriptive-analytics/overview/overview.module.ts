import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { OverviewPage } from "./overview.page";
import { RouterModule } from "@angular/router";
import { OverviewRoutingModule } from "./overview-routing.module";

const PAGES = [
  OverviewPage,
];

@NgModule({
  declarations: [
    ...PAGES,
  ],
  imports: [
    OverviewRoutingModule,
    CommonModule,
    RouterModule,
  ]
})
export class OverviewModule {}
