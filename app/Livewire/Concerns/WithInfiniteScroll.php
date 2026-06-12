<?php

namespace App\Livewire\Concerns;

trait WithInfiniteScroll
{
    public int $perPage = 12;

    public int $pageSize = 12;

    public function loadMore(): void
    {
        $this->perPage += $this->pageSize;
    }

    protected function resetInfiniteScroll(): void
    {
        $this->perPage = $this->pageSize;
    }
}
