import { Component, Input, OnInit } from '@angular/core';

interface Tab {
  label: string;
  route: string;
  key: string;
}

@Component({
  selector: 'app-tabs',
  templateUrl: './tabs.component.html',
  styleUrls: ['./tabs.component.scss']
})
export class TabsComponent implements OnInit {
  @Input() tabs: Tab[] = [];
  public activeTab: string = '';

  public ngOnInit() {
    if (this.tabs.length > 0) {
      this.activeTab = this.tabs[0].key;
    }
  }

  public setActive(key: string) {
    this.activeTab = key;
  }

  public isActive(key: string): boolean {
    return this.activeTab === key;
  }
}
