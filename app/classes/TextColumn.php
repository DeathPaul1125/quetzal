<?php

class TextColumn extends QuetzalTableColumn
{
  public function render(array $row): string
  {
    return htmlspecialchars((string)($row[$this->name] ?? ''), ENT_QUOTES);
  }
}
