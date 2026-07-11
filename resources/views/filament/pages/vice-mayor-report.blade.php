<x-filament-panels::page>
    <x-filament-widgets::widgets
        :columns="$this->getHeaderWidgetsColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getVisibleHeaderWidgets()"
    />

    <x-filament-widgets::widgets
        :columns="$this->getFooterWidgetsColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getVisibleFooterWidgets()"
        class="mt-6"
    />
</x-filament-panels::page>
