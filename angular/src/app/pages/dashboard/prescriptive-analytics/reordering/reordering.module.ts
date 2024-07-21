import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { ReorderingPage } from "./reordering.page";
import { RouterModule } from "@angular/router";
import { ReorderingRoutingModule } from "./reordering-routing.module";

const PAGES = [
  ReorderingPage,
];

@NgModule({
  declarations: [
    ...PAGES,
  ],
  imports: [
    ReorderingRoutingModule,
    CommonModule,
    RouterModule,
  ]
})
export class ReorderingModule {}
