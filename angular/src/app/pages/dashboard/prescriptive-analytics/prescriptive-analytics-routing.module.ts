import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";
import { ReorderingPage } from "./reordering/reordering.page";

const routes: Routes = [
  {
    path: '',
    redirectTo: 'reordering',
    pathMatch: 'full'
  },
  {
    path: 'reordering',
    component: ReorderingPage,
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PrescriptiveAnalyticsRoutingModule {}
