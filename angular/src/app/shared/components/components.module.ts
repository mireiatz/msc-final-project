import { SidebarComponent } from "./sidebar/sidebar.component";
import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { RouterModule } from "@angular/router";
import { TabsComponent } from "./tabs/tabs.component";
import { TableComponent } from "./table/table.component";
import { DateRangePickerComponent } from "./date-range-picker/date-range-picker.component";
import { FormsModule } from "@angular/forms";

const COMPONENTS = [
  SidebarComponent,
  TabsComponent,
  TableComponent,
  DateRangePickerComponent,
]
@NgModule({
  declarations: [
    ...COMPONENTS,
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule,
  ],
  providers: [],
  exports: [
    ...COMPONENTS,
  ]
})
export class ComponentsModule {}
