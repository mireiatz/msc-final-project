import { SidebarComponent } from "./sidebar/sidebar.component";
import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { RouterModule } from "@angular/router";
import { TabsComponent } from "./tabs/tabs.component";

const COMPONENTS = [
  SidebarComponent,
  TabsComponent,
]
@NgModule({
  declarations: [
    ...COMPONENTS,
  ],
  imports: [
    CommonModule,
    RouterModule,
  ],
  providers: [],
  exports: [
    ...COMPONENTS,
  ]
})
export class ComponentsModule {}
