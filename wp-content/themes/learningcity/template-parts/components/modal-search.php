<div data-modal-content="modal-search" class="modal items-end p-0!">
    <div class="overlay-modal"></div>
    <div class="card-modal rounded-none! max-w-full">
        <div class="absolute top-4 right-4 z-20 lc-modal-search-close">
            <button class="close-modal bg-black rounded-full size-8 flex gap-2 justify-center items-center p-2.5" aria-label="ปิดหน้าค้นหา">
                <div class="icon-close"></div>
            </button>
        </div>

        <div class="modal-content relative z-10 group lc-modal-search-shell">
            <div class="sm:px-8 px-4 lc-modal-search-scroll">
                <div class="lc-modal-search max-w-[1140px] mx-auto">
                    <div class="lc-modal-search__head max-w-[820px] mx-auto">
                        <label for="lc-modal-search-input" class="sr-only">ค้นหา</label>
                        <div class="lc-modal-search__input-wrap">
                            <span class="icon-search block w-4.5"></span>
                            <input
                                id="lc-modal-search-input"
                                class="lc-modal-search__input"
                                type="search"
                                autocomplete="off"
                                placeholder="ค้นหา Next Learn หรือแหล่งเรียนรู้"
                            >
                        </div>
                        <p id="lc-modal-search-meta" class="lc-modal-search__meta">พิมพ์เพื่อค้นหาแบบเรียลไทม์</p>
                    </div>

                    <div id="lc-modal-search-results" class="lc-modal-search__results">
                        <section class="lc-search-section" data-section="quick">
                            <header class="lc-search-section__head">
                                <h3 id="lc-search-quick-title">คำค้นหาบ่อย</h3>
                            </header>
                            <div class="lc-search-section__list" data-list="quick"></div>
                        </section>

                        <section class="lc-search-section" data-section="nextlearn">
                            <header class="lc-search-section__head">
                                <h3>Next Learn</h3>
                            </header>
                            <div class="lc-search-section__list" data-list="nextlearn"></div>
                        </section>

                        <section class="lc-search-section" data-section="locations">
                            <header class="lc-search-section__head">
                                <h3>แหล่งเรียนรู้</h3>
                            </header>
                            <div class="lc-search-section__list" data-list="locations"></div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
