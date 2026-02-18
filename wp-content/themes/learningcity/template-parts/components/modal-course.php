<div data-modal-content="modal-course" class="modal modal-course p-0!">
  <div class="overlay-modal"></div>

  <div class="card-modal max-w-full">
    <div class="absolute top-4 right-4 z-20">
      <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5">
        <div class="icon-close"></div>
      </button>
    </div>

    <div class="modal-content relative z-10 overflow-y-auto! group h-full bg-[#fcfcfc]">
        <div class="modal-course-gradient absolute top-0 left-0 w-full sm:h-96 h-[640px]"
        style="background: linear-gradient(0deg, #fcfcfc 10%, #00744b33 45%);">
        </div>

      <div class="py-3 px-4 relative">

        <!-- Top actions -->
        <div class="flex items-center justify-start gap-2">
          <button class="btn-link-v1" data-course-copy-link>
            <i class="icon-copy-link"></i>
            <span class="btn-text">คัดลอกลิงก์</span>
          </button>

          <a class="btn-link-v1" data-course-open-link target="_blank">
            <i class="icon-open-link"></i>
            เปิดในหน้าใหม่
          </a>
        </div>

        <!-- Skeleton (โชว์ตอนกำลังโหลด) -->
        <div class="skeleton relative w-full z-10" data-course-skeleton>
          <div class="md:px-12 sm:px-6 px-0 mt-6 max-sm:mt-12">
            <div class="flex sm:items-end sm:flex-row flex-col-reverse items-center max-sm:text-center gap-4">
              <div class="flex-1 max-sm:mt-2 w-full">
                <div class="h-4 sm:w-1/2 w-full mb-2" data-skeleton></div>
                <div class="h-12 sm:w-4/5 w-full" data-skeleton></div>
                <div class="flex items-center justify-start gap-2 mt-2 max-sm:w-full mx-auto">
                  <div class="h-4 w-4" data-skeleton></div>
                  <div class="h-4 sm:w-1/2 w-full" data-skeleton></div>
                </div>
              </div>
              <div class="w-32 max-sm:w-36 aspect-square" data-skeleton></div>
            </div>

            <div class="flex sm:flex-row flex-col items-center overflow-hidden my-8 w-full" data-skeleton>
              <div class="py-5 flex items-center justify-evenly flex-1 w-full">
                <?php for ($i=0; $i<4; $i++): ?>
                  <div class="text-center flex-1">
                    <div class="h-5 w-5 mt-1 mx-auto" data-skeleton></div>
                    <div class="h-3 w-1/2 mt-1 mx-auto" data-skeleton></div>
                    <div class="h-4 w-1/2 mt-1 mx-auto" data-skeleton></div>
                  </div>
                <?php endfor; ?>
              </div>
            </div>

            <div class="pt-1">
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-1/2 h-4 mt-3" data-skeleton></div>
            </div>

            <div class="my-10">
              <div class="w-1/3 h-8" data-skeleton></div>
              <div class="w-full h-20 mt-2" data-skeleton></div>
              <div class="w-full h-20 mt-2" data-skeleton></div>
              <div class="w-full h-20 mt-2" data-skeleton></div>
            </div>

            <div class="my-10">
              <div class="w-1/3 h-8" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
            </div>

            <div class="my-10">
              <div class="w-1/3 h-8" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
              <div class="w-full h-4 mt-3" data-skeleton></div>
            </div>
          </div>
        </div>

        <!-- AJAX จะยัด HTML เข้ามาตรงนี้ -->
        <div class="content-inner is-loaded md:px-12 sm:px-6 px-0 mt-6 transition-all max-sm:mt-12"
             data-course-modal-body
             style="display:none;">
        </div>

      </div>
    </div>
  </div>
</div>
