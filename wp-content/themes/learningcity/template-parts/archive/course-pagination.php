<?php
$max_pages = (int) get_query_var('max_pages');
$paged     = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

$links = paginate_links([
  'total'     => max(1, $max_pages),
  'current'   => $paged,
  'type'      => 'array',
  'prev_text' => 'ก่อนหน้า',
  'next_text' => 'ถัดไป',
]);

if (!empty($links)) :

  // base classes
  $base = 'inline-flex items-center justify-center min-w-10 h-10 px-3 rounded-full border text-sm font-medium transition';
  $item = $base . ' bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400';
  $curr = $base . ' bg-emerald-700 text-white border-emerald-700';
  $dots = $base . ' bg-transparent text-gray-400 border-transparent cursor-default';
  $nav  = $base . ' bg-white text-gray-700 border-gray-300 hover:bg-gray-50';

?>
  <nav class="mt-8" aria-label="Pagination">
    <ul class="flex flex-wrap items-center gap-2">
      <?php foreach ($links as $link) :

        // 1) current page (span.current)
        if (strpos($link, 'current') !== false) {
          $link = str_replace(
            "class='page-numbers current'",
            "class='page-numbers current {$curr}'",
            $link
          );
          $link = str_replace(
            'class="page-numbers current"',
            "class=\"page-numbers current {$curr}\"",
            $link
          );
        }
        // 2) dots
        elseif (strpos($link, 'dots') !== false) {
          $link = str_replace(
            "class='page-numbers dots'",
            "class='page-numbers dots {$dots}'",
            $link
          );
          $link = str_replace(
            'class="page-numbers dots"',
            "class=\"page-numbers dots {$dots}\"",
            $link
          );
        }
        // 3) prev/next
        elseif (strpos($link, 'prev') !== false || strpos($link, 'next') !== false) {
          // ใส่ padding เพิ่มให้ปุ่ม nav
          $navBtn = $nav . ' px-4';
          $link = str_replace(
            "class='page-numbers",
            "class='page-numbers {$navBtn}",
            $link
          );
          $link = str_replace(
            'class="page-numbers',
            "class=\"page-numbers {$navBtn}",
            $link
          );
        }
        // 4) normal page link
        else {
          $link = str_replace(
            "class='page-numbers",
            "class='page-numbers {$item}",
            $link
          );
          $link = str_replace(
            'class="page-numbers',
            "class=\"page-numbers {$item}",
            $link
          );
        }

        // (optional) เติม aria-current ให้ current ให้ดีขึ้น
        if (strpos($link, 'current') !== false && strpos($link, 'aria-current') === false) {
          $link = str_replace('<span', '<span aria-current="page"', $link);
        }

        echo '<li>' . $link . '</li>';

      endforeach; ?>
    </ul>
  </nav>
<?php endif; ?>
