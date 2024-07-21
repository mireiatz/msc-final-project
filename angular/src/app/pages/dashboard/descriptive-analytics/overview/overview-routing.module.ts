import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";
import { OverviewPage } from "./overview.page";

const routes: Routes = [
  {
    path: '',
    component: OverviewPage,
    pathMatch: 'full',
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class OverviewRoutingModule {}
