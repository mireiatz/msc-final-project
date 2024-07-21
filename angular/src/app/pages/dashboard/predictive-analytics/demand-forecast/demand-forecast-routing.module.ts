import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";
import { DemandForecastPage } from "./demand-forecast.page";

const routes: Routes = [
  {
    path: '',
    component: DemandForecastPage,
    pathMatch: 'full',
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DemandForecastRoutingModule {}
