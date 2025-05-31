<?php

namespace App;

class BookingDataService
{
    private string $csvFile;

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;
    }

    public function getAll(): array
    {
        $rows = array_map('str_getcsv', file($this->csvFile));
        $header = array_shift($rows);
        $result = [];
        foreach ($rows as $row) {
            $result[] = array_combine($header, $row);
        }
        return $result;
    }

    public function add(array $data): void
    {
        $all = $this->getAll();
        $data['id'] = count($all) + 1;
        $fp = fopen($this->csvFile, 'a');
        fputcsv($fp, $data);
        fclose($fp);
    }

    public function update(int $id, array $data): void
    {
        $all = $this->getAll();
        foreach ($all as &$row) {
            if ((int)$row['id'] === $id) {
                foreach ($data as $k => $v) {
                    $row[$k] = $v;
                }
            }
        }
        $this->writeAll($all);
    }

    public function delete(int $id): void
    {
        $all = $this->getAll();
        $all = array_filter($all, fn($row) => (int)$row['id'] !== $id);
        $this->writeAll($all);
    }

    private function writeAll(array $rows): void
    {
        $fp = fopen($this->csvFile, 'w');
        fputcsv($fp, ['id','phone','house_id','comment','created_at']);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
} 