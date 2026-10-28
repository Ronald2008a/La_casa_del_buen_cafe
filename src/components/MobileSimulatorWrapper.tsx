import React from 'react';
import { Wifi, Battery, Signal, Smartphone, X } from 'lucide-react';

interface MobileSimulatorWrapperProps {
  isMobileMode: boolean;
  onCloseMobileMode: () => void;
  children: React.ReactNode;
}

export const MobileSimulatorWrapper: React.FC<MobileSimulatorWrapperProps> = ({
  isMobileMode,
  onCloseMobileMode,
  children
}) => {
  if (!isMobileMode) {
    return <>{children}</>;
  }

  return (
    <div className="min-h-screen bg-[#2A2321] py-6 px-4 flex flex-col items-center justify-center">
      {/* Simulator Control Banner */}
      <div className="mb-4 flex items-center justify-between w-full max-w-[420px] px-3 py-2 bg-[#372F2C] text-[#FDEEEA] rounded-full text-xs border border-[#55433E]">
        <div className="flex items-center gap-2">
          <Smartphone className="w-4 h-4 text-[#C86D51]" />
          <span className="font-medium">Artisanal Mobile Screen Mode (390px)</span>
        </div>
        <button
          onClick={onCloseMobileMode}
          className="text-[#DBC1BA] hover:text-white flex items-center gap-1 font-semibold underline underline-offset-2 ml-3"
        >
          <span>Exit to Full Desktop</span>
          <X className="w-3.5 h-3.5" />
        </button>
      </div>

      {/* Phone Hardware Mockup */}
      <div className="relative w-full max-w-[400px] h-[860px] bg-[#1E1715] rounded-[48px] p-3 shadow-2xl border-4 border-[#3D3330] flex flex-col overflow-hidden ring-1 ring-white/10">
        
        {/* Dynamic Island / Notch */}
        <div className="absolute top-4 left-1/2 -translate-x-1/2 w-28 h-6 bg-black rounded-full z-40 flex items-center justify-end px-3">
          <div className="w-2.5 h-2.5 rounded-full bg-[#1A1A1A] border border-neutral-700 mr-1" />
        </div>

        {/* Status Bar */}
        <div className="h-9 px-6 pt-1 flex items-center justify-between text-xs font-semibold text-[#211A18] bg-[#FFF8F6] rounded-t-[40px] z-30 select-none">
          <span className="text-[12px] font-mono">9:41</span>
          <div className="flex items-center gap-1.5 text-[11px]">
            <Signal className="w-3 h-3" />
            <Wifi className="w-3 h-3" />
            <Battery className="w-4 h-4" />
          </div>
        </div>

        {/* Screen Viewport Scrollable Area */}
        <div className="flex-1 bg-[#FFF8F6] overflow-y-auto scrollbar-none rounded-b-[40px] relative">
          {children}
        </div>

        {/* Bottom Home Indicator Bar */}
        <div className="absolute bottom-1 left-1/2 -translate-x-1/2 w-32 h-1 bg-[#211A18]/40 rounded-full z-40 pointer-events-none" />
      </div>

      <p className="mt-4 text-xs text-[#88726C] text-center max-w-sm">
        Simulating the 4-column fluid mobile grid, 52px thumb touch buttons, and tactile ceramic styling from the design system.
      </p>
    </div>
  );
};
