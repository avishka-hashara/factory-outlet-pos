{{-- ============================================
     Reusable Product Detail Slide-Over Drawer
     Teleported to <body> with pinned header/footer & min-h-0 scroll body
     ============================================ --}}
<template x-teleport="body">
    <div x-show="drawerOpen"
         @keydown.escape.window="drawerOpen = false"
         class="fixed inset-0 z-[9999] overflow-hidden flex justify-end"
         style="display: none;"
         role="dialog"
         aria-modal="true">

        <!-- Overlay / Backdrop -->
        <div x-show="drawerOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="drawerOpen = false"
             class="fixed inset-0 bg-black/70 backdrop-blur-xs transition-opacity"></div>

        <!-- Panel: h-full flex flex-col overflow-hidden, right-aligned -->
        <div x-show="drawerOpen"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="relative w-screen max-w-md h-full bg-[#13151A] border-l border-[#272C35] shadow-2xl flex flex-col overflow-hidden z-10">

            <!-- Pinned Header -->
            <div class="shrink-0 p-4 border-b border-[#272C35] flex items-center justify-between bg-[#111419]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[22px] text-amber-accent">inventory_2</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-[15px] text-white tracking-tight m-0 truncate max-w-[220px]"
                                x-text="activeProduct?.name"></h3>
                            
                            <!-- Dynamic Status Pill -->
                            <template x-if="activeProduct?.stock > 0">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Active
                                </span>
                            </template>
                            <template x-if="activeProduct?.stock <= 0">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/25 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>Out of Stock
                                </span>
                            </template>
                        </div>
                        <div class="flex items-center gap-2 text-[11px] text-[#8A8A93] mt-0.5">
                            <span class="font-mono" x-text="'SKU: ' + (activeProduct?.sku || '—')"></span>
                            <span>&middot;</span>
                            <span x-text="activeProduct?.category || 'Uncategorized'"></span>
                        </div>
                    </div>
                </div>
                <button type="button"
                        @click="drawerOpen = false"
                        class="dashboard-control w-8 h-8 rounded-[4px] text-[#8A8A93] hover:text-white flex items-center justify-center cursor-pointer border-0"
                        title="Close drawer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Scrollable Body (min-h-0 is required) -->
            <div class="flex-1 min-h-0 overflow-y-auto p-4 custom-scrollbar flex flex-col gap-4">

                <!-- Product Hero Card (Image + Pricing + Source) -->
                <div class="p-3.5 rounded-[8px] bg-[#171A20] border border-[#272C35] flex items-center gap-3.5">
                    <!-- Thumbnail / Image -->
                    <div class="w-18 h-18 rounded-[8px] bg-[#111419] border border-[#272C35] flex items-center justify-center overflow-hidden shrink-0">
                        <template x-if="activeProduct?.image_url">
                            <img :src="activeProduct.image_url" :alt="activeProduct.name" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!activeProduct?.image_url">
                            <span class="material-symbols-outlined text-[28px] text-amber-accent/40">apparel</span>
                        </template>
                    </div>

                    <div class="flex-1 min-w-0">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] block">Retail Price</span>
                        <div class="font-['Inter'] font-bold text-[20px] text-white tabular-nums tracking-tight"
                             x-text="activeProduct?.price_formatted"></div>
                        
                        <div class="flex items-center gap-2 mt-1">
                            <template x-if="activeProduct?.is_shopify">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/20 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">cloud_sync</span> Shopify Synced
                                </span>
                            </template>
                            <template x-if="!activeProduct?.is_shopify">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px]">store</span> In-Store (Local)
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Stock Inventory Status Card -->
                <div class="p-3.5 rounded-[8px] bg-[#171A20] border border-[#272C35] flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider">Inventory & Stock Level</span>
                        <template x-if="activeProduct?.stock > 5">
                            <span class="text-[11px] font-bold text-emerald-400">Healthy Stock</span>
                        </template>
                        <template x-if="activeProduct?.stock > 0 && activeProduct?.stock <= 5">
                            <span class="text-[11px] font-bold text-amber-400">Low Stock Alert</span>
                        </template>
                        <template x-if="activeProduct?.stock <= 0">
                            <span class="text-[11px] font-bold text-rose-400">Out of Stock</span>
                        </template>
                    </div>

                    <div class="flex items-baseline justify-between">
                        <div class="flex items-baseline gap-1.5">
                            <span class="font-['Inter'] font-bold text-[24px] tabular-nums"
                                  :class="activeProduct?.stock > 5 ? 'text-white' : (activeProduct?.stock > 0 ? 'text-amber-400' : 'text-rose-400')"
                                  x-text="activeProduct?.stock"></span>
                            <span class="text-[11px] text-[#8A8A93]">units available</span>
                        </div>
                        <span class="text-[11px] text-[#8A8A93]">Reorder Point: 5</span>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-full h-1.5 bg-[#111419] rounded-full overflow-hidden border border-[#272C35]">
                        <div class="h-full rounded-full transition-all duration-300"
                             :class="activeProduct?.stock > 5 ? 'bg-emerald-400' : (activeProduct?.stock > 0 ? 'bg-amber-400' : 'bg-rose-500')"
                             :style="`width: ${Math.min(100, Math.max(0, (activeProduct?.stock / 20) * 100))}%`"></div>
                    </div>
                </div>

                <!-- Product Specifications -->
                <div class="p-3.5 rounded-[8px] bg-[#171A20] border border-[#272C35] flex flex-col gap-2 text-[12px]">
                    <span class="text-[10px] uppercase font-bold text-[#8A8A93] tracking-wider mb-0.5">Specifications</span>

                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                        <div>
                            <span class="text-[#8A8A93] block">Category:</span>
                            <span class="text-white font-medium" x-text="activeProduct?.category || '—'"></span>
                        </div>
                        <div>
                            <span class="text-[#8A8A93] block">Sub-Category:</span>
                            <span class="text-white font-medium" x-text="activeProduct?.subcategory || '—'"></span>
                        </div>
                        <div>
                            <span class="text-[#8A8A93] block">Base SKU:</span>
                            <span class="font-mono text-[#d7c3ae]" x-text="activeProduct?.sku || '—'"></span>
                        </div>
                        <div>
                            <span class="text-[#8A8A93] block">Stock Value:</span>
                            <span class="font-bold text-amber-accent tabular-nums"
                                  x-text="'Rs. ' + (Number(activeProduct?.price || 0) * Number(activeProduct?.stock || 0)).toLocaleString('en-US', {minimumFractionDigits:2})"></span>
                        </div>
                    </div>
                </div>

                <!-- Variants Breakdown List -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-[11px] font-bold text-[#8A8A93] uppercase tracking-wider px-1">
                        <span>Variants & Options</span>
                        <span x-text="(activeProduct?.variants?.length || 0) + ' variants'"></span>
                    </div>

                    <div class="rounded-[8px] bg-[#171A20] border border-[#272C35] overflow-hidden divide-y divide-[#272C35]/50">
                        <template x-for="v in (activeProduct?.variants || [])" :key="v.id">
                            <div class="p-2.5 flex items-center justify-between text-[12px]">
                                <div class="flex items-center gap-2">
                                    <template x-if="v.color_code">
                                        <span class="w-4 h-4 rounded-full border border-white/20 shrink-0"
                                              :style="`background-color: ${v.color_code};`"></span>
                                    </template>
                                    <div>
                                        <div class="text-white font-medium" x-text="v.label"></div>
                                        <div class="text-[10px] font-mono text-[#8A8A93]" x-text="'SKU: ' + (v.sku || activeProduct.sku)"></div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="font-bold tabular-nums"
                                         :class="v.stock > 0 ? 'text-emerald-400' : 'text-rose-400'"
                                         x-text="v.stock + ' in stock'"></div>
                                    <template x-if="v.price">
                                        <div class="text-[10px] text-[#8A8A93] tabular-nums"
                                             x-text="'Rs. ' + Number(v.price).toLocaleString('en-US', {minimumFractionDigits:2})"></div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="!activeProduct?.variants || activeProduct?.variants?.length === 0">
                            <div class="p-4 text-center text-[#8A8A93] text-[11px]">
                                Standard single-variant product.
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Pinned Footer Actions -->
            <div class="shrink-0 p-3.5 border-t border-[#272C35] bg-[#111419] flex items-center gap-2">
                <template x-if="!activeProduct?.is_shopify">
                    <button type="button"
                            @click="const p = activeProduct; drawerOpen = false; openEditModal(p)"
                            class="flex-1 h-[36px] bg-[#F5A623] hover:bg-[#F5A623]/90 text-[#14161A] font-bold text-[11px] uppercase tracking-wider rounded-[6px] flex items-center justify-center gap-1.5 transition-colors cursor-pointer border-0 active:scale-95">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                        <span>Edit Product</span>
                    </button>
                </template>

                <button type="button"
                        @click="const p = activeProduct; drawerOpen = false; openVariantsModal(p)"
                        class="dashboard-control h-[36px] px-3 text-[#d7c3ae] hover:text-white rounded-[6px] flex items-center justify-center gap-1 text-[11px] font-semibold uppercase tracking-wider cursor-pointer border-0">
                    <span class="material-symbols-outlined text-[16px]">palette</span>
                    <span>Variants</span>
                </button>

                <template x-if="!activeProduct?.is_shopify">
                    <button type="button"
                            @click="const p = activeProduct; drawerOpen = false; openBarcodeModal(p)"
                            class="dashboard-control h-[36px] px-3 text-[#d7c3ae] hover:text-white rounded-[6px] flex items-center justify-center gap-1 text-[11px] font-semibold uppercase tracking-wider cursor-pointer border-0"
                            title="Print Barcode">
                        <span class="material-symbols-outlined text-[16px]">barcode</span>
                    </button>
                </template>
            </div>

        </div>
    </div>
</template>
