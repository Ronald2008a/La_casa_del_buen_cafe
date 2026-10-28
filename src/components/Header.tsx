import React from 'react';
import { ShoppingBag, Coffee, Smartphone, Monitor } from 'lucide-react';

interface HeaderProps {
  activeTab: 'menu' | 'origins' | 'brew-lab' | 'order-status';
  onSelectTab: (tab: 'menu' | 'origins' | 'brew-lab' | 'order-status') => void;
  cartCount: number;
  onOpenCart: () => void;
  isMobileFrame: boolean;
  onToggleMobileFrame: () => void;
  hasActiveOrder: boolean;
}

export const Header: React.FC<HeaderProps> = ({
  activeTab,
  onSelectTab,
  cartCount,
  onOpenCart,
  isMobileFrame,
  onToggleMobileFrame,
  hasActiveOrder
}) => {
  return (
    <header className="sticky top-0 z-30 bg-[#FFF8F6]/95 backdrop-blur-md border-b border-[#EFE8E1]">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between gap-6">
        
        {/* Zone 1: Single Brand Wordmark element */}
        <button 
          onClick={() => onSelectTab('menu')}
          className="flex items-center gap-2.5 text-left group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#94452C] rounded-md px-1 py-0.5"
        >
          <div className="w-9 h-9 rounded-full bg-[#94452C] text-white flex items-center justify-center transition-transform group-hover:scale-105 duration-200">
            <Coffee className="w-5 h-5 text-white" />
          </div>
          <div className="flex flex-col">
            <span className="font-heading text-lg sm:text-xl font-bold tracking-tight text-[#211A18] whitespace-nowrap">
              ATELIER ROASTERY
            </span>
          </div>
        </button>

        {/* Zone 2: Concise single-line navigation links */}
        <nav className="hidden md:flex items-center gap-8 text-sm font-medium">
          <button
            onClick={() => onSelectTab('menu')}
            className={`whitespace-nowrap transition-colors relative py-1 ${
              activeTab === 'menu'
                ? 'text-[#94452C] font-semibold'
                : 'text-[#55433E] hover:text-[#211A18]'
            }`}
          >
            Artisanal Menu
            {activeTab === 'menu' && (
              <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#94452C] rounded-full" />
            )}
          </button>

          <button
            onClick={() => onSelectTab('origins')}
            className={`whitespace-nowrap transition-colors relative py-1 ${
              activeTab === 'origins'
                ? 'text-[#94452C] font-semibold'
                : 'text-[#55433E] hover:text-[#211A18]'
            }`}
          >
            Single Origins & Terroirs
            {activeTab === 'origins' && (
              <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#94452C] rounded-full" />
            )}
          </button>

          <button
            onClick={() => onSelectTab('brew-lab')}
            className={`whitespace-nowrap transition-colors relative py-1 ${
              activeTab === 'brew-lab'
                ? 'text-[#94452C] font-semibold'
                : 'text-[#55433E] hover:text-[#211A18]'
            }`}
          >
            Brew Ratio Lab
            {activeTab === 'brew-lab' && (
              <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#94452C] rounded-full" />
            )}
          </button>

          <button
            onClick={() => onSelectTab('order-status')}
            className={`whitespace-nowrap flex items-center gap-2 transition-colors relative py-1 ${
              activeTab === 'order-status'
                ? 'text-[#94452C] font-semibold'
                : 'text-[#55433E] hover:text-[#211A18]'
            }`}
          >
            Live Order Status
            {hasActiveOrder && (
              <span className="w-2 h-2 rounded-full bg-[#94452C]" />
            )}
            {activeTab === 'order-status' && (
              <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#94452C] rounded-full" />
            )}
          </button>
        </nav>

        {/* Zone 3: Primary Action & Viewport Simulator Toggle */}
        <div className="flex items-center gap-3 shrink-0">
          {/* Viewport switcher: Desktop vs Mobile App Mode */}
          <button
            onClick={onToggleMobileFrame}
            title={isMobileFrame ? 'Switch to Full Desktop View' : 'Switch to Mobile App Preview (390px)'}
            className="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-[#5C3D2E] bg-[#FAEDE8] hover:bg-[#F4E3DD] border border-[#EFE8E1] rounded-full transition-colors whitespace-nowrap"
          >
            {isMobileFrame ? (
              <>
                <Monitor className="w-3.5 h-3.5" />
                <span>Desktop Mode</span>
              </>
            ) : (
              <>
                <Smartphone className="w-3.5 h-3.5" />
                <span>Mobile App View</span>
              </>
            )}
          </button>

          {/* Cart Bag Trigger */}
          <button
            onClick={onOpenCart}
            aria-label="View shopping bag"
            className="flex items-center gap-2 px-4 py-2 bg-[#94452C] hover:bg-[#803B25] text-white rounded-full transition-all active:scale-95 shadow-sm whitespace-nowrap"
          >
            <ShoppingBag className="w-4 h-4" />
            <span className="text-xs sm:text-sm font-semibold">
              Bag {cartCount > 0 && `(${cartCount})`}
            </span>
          </button>
        </div>

      </div>

      {/* Mobile sub-navigation for small screens */}
      <div className="md:hidden flex items-center justify-around border-t border-[#EFE8E1] bg-[#FFF8F6] px-2 py-2 overflow-x-auto scrollbar-none text-xs">
        <button
          onClick={() => onSelectTab('menu')}
          className={`px-3 py-1.5 rounded-full whitespace-nowrap transition-colors ${
            activeTab === 'menu'
              ? 'bg-[#211A18] text-white font-medium'
              : 'text-[#55433E]'
          }`}
        >
          Menu
        </button>
        <button
          onClick={() => onSelectTab('origins')}
          className={`px-3 py-1.5 rounded-full whitespace-nowrap transition-colors ${
            activeTab === 'origins'
              ? 'bg-[#211A18] text-white font-medium'
              : 'text-[#55433E]'
          }`}
        >
          Origins
        </button>
        <button
          onClick={() => onSelectTab('brew-lab')}
          className={`px-3 py-1.5 rounded-full whitespace-nowrap transition-colors ${
            activeTab === 'brew-lab'
              ? 'bg-[#211A18] text-white font-medium'
              : 'text-[#55433E]'
          }`}
        >
          Brew Lab
        </button>
        <button
          onClick={() => onSelectTab('order-status')}
          className={`px-3 py-1.5 rounded-full whitespace-nowrap transition-colors flex items-center gap-1.5 ${
            activeTab === 'order-status'
              ? 'bg-[#211A18] text-white font-medium'
              : 'text-[#55433E]'
          }`}
        >
          <span>Order Status</span>
          {hasActiveOrder && (
            <span className="w-1.5 h-1.5 rounded-full bg-[#94452C]" />
          )}
        </button>
      </div>
    </header>
  );
};
