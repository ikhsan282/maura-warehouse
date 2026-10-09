<?php
// Minimal PDF 1.4 writer: text lines, simple tables, page breaks, Helvetica
class SimplePDF {
    private $offsets = [];
    private $pages = [];
    private $current_page = null;
    private $y = 750;
    private $page_height = 792; // Letter
    private $page_width = 612;
    private $margin = 50;
    private $line_height = 14;

    public function __construct() {
        $this->addPage(); // ensure at least one page
    }

    // Helvetica (WinAnsi-free base-14 use here) only safely renders printable ASCII.
    // Non-ASCII (e.g. UTF-8 from DB) becomes '?', then ( ) \ are escaped.
    private function escape_string(string $s): string {
        $s = preg_replace('/[^\x20-\x7E]/', '?', $s);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    public function addPage(): void {
        if ($this->current_page !== null) $this->pages[] = $this->current_page;
        $this->current_page = ['content' => ''];
        $this->y = $this->page_height - $this->margin;
    }

    public function addText(string $text, int $size = 10): void {
        if ($this->y < $this->margin + 20) $this->addPage();
        $escaped = $this->escape_string($text);
        $this->current_page['content'] .= sprintf(
            "BT /F1 %d Tf %d %d Td (%s) Tj ET\n",
            $size, $this->margin, (int)$this->y, $escaped
        );
        $this->y -= $size + 4;
    }

    // Truncate by character count (mbstring-free: multibyte-safe via UTF-8 boundary walk)
    private function clip(string $s, int $max_chars): string {
        if ($max_chars < 1) return '';
        $count = 0; $i = 0; $len = strlen($s);
        while ($i < $len && $count < $max_chars) {
            $b = ord($s[$i]);
            $i += ($b < 0x80) ? 1 : (($b >> 5) === 0x6 ? 2 : (($b >> 4) === 0xE ? 3 : (($b >> 3) === 0x1E ? 4 : 1)));
            $count++;
        }
        return substr($s, 0, $i);
    }

    public function addTableRow(array $cells, array $widths, bool $bold = false): void {
        if ($this->y < $this->margin + 20) $this->addPage();
        $x = $this->margin;
        $size = $bold ? 10 : 9;
        foreach ($cells as $i => $cell) {
            $w = $widths[$i] ?? 80;
            $text = $this->clip((string)$cell, (int)($w / 5)); // rough char limit
            $escaped = $this->escape_string($text);
            $this->current_page['content'] .= sprintf(
                "BT /F1 %d Tf %d %d Td (%s) Tj ET\n",
                $size, (int)$x, (int)$this->y, $escaped
            );
            $x += $w;
        }
        $this->y -= $this->line_height;
    }

    public function output(): string {
        $pdf = "%PDF-1.4\n";
        $obj_num = 1;
        $pages = array_merge($this->pages, [$this->current_page]); // include the open page
        $num_pages = count($pages);
        // Assign object IDs
        $catalog_id = $obj_num++;      // 1
        $pages_id = $obj_num++;        // 2
        $font_id = $obj_num++;         // 3
        $page_ids = [];
        for ($i = 0; $i < $num_pages; $i++) $page_ids[] = $obj_num++;
        $content_ids = [];
        for ($i = 0; $i < $num_pages; $i++) $content_ids[] = $obj_num++;

        // Build objects in order, recording xref offsets
        $build = function(int $id, string $content) use (&$pdf, &$obj_num) {
            $this->offsets[$id] = strlen($pdf);
            $pdf .= $content;
        };

        // Catalog
        $build($catalog_id, sprintf(
            "%d 0 obj\n<< /Type /Catalog /Pages %d 0 R >>\nendobj\n",
            $catalog_id, $pages_id
        ));

        // Pages
        $kids = implode(' ', array_map(fn($id) => "$id 0 R", $page_ids));
        $build($pages_id, sprintf(
            "%d 0 obj\n<< /Type /Pages /Kids [%s] /Count %d >>\nendobj\n",
            $pages_id, $kids, $num_pages
        ));

        // Font
        $build($font_id, sprintf(
            "%d 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            $font_id
        ));

        // Page objects
        for ($i = 0; $i < $num_pages; $i++) {
            $build($page_ids[$i], sprintf(
                "%d 0 obj\n<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %d %d] /Contents %d 0 R /Resources << /Font << /F1 %d 0 R >> >> >>\nendobj\n",
                $page_ids[$i], $pages_id, $this->page_width, $this->page_height,
                $content_ids[$i], $font_id
            ));
        }

        // Content streams
        for ($i = 0; $i < $num_pages; $i++) {
            $content = $pages[$i]['content'];
            $build($content_ids[$i], sprintf(
                "%d 0 obj\n<< /Length %d >>\nstream\n%s\nendstream\nendobj\n",
                $content_ids[$i], strlen($content), $content
            ));
        }

        // xref
        $total_objs = 1 + count($this->offsets); // 0 + objects
        $xref_offset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= sprintf("0 %d\n", $total_objs);
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $total_objs; $i++) {
            $off = $this->offsets[$i] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }

        $pdf .= sprintf("trailer\n<< /Size %d /Root %d 0 R >>\n", $total_objs, $catalog_id);
        $pdf .= "startxref\n$xref_offset\n%%EOF";

        return $pdf;
    }
}
