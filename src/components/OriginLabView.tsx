import React, { useState, useEffect } from 'react';
import { Product } from '../types/coffee';
import { COFFEE_PRODUCTS } from '../data/coffeeData';
import { Play, Pause, RotateCcw, Scale, Thermometer, Compass, Sparkles, Droplets } from 'lucide-react';

interface OriginLabViewProps {
  onSelectProduct: (product: Product) => void;
}

type BrewDevice = 'v60' | 'chemex' | 'aeropress' | 'frenchpress' | 'coldbrew';

interface DeviceConfig {
  name: string;
  ratio: number; // water g per 1g coffee
  temp: string;
  grind: string;
  bloomTimeSec: number;
  totalTimeSec: number;
  instructions: Array<{ time: string; action: string }>;
}

const BREW_CONFIGS: Record<BrewDevice, DeviceConfig> = {
  v60: {
    name: 'Hario V60 Ceramic',
    ratio: 16.5,
    temp: '93°C (200°F)',
    grind: 'Medium-Fine (table salt consistency)',
    bloomTimeSec: 45,
    totalTimeSec: 180,
    instructions: [
      { time: '0:00 - 0:45', action: 'Bloom: Pour 3x dry weight in spiral motion to release CO2' },
      { time: '0:45 - 1:15', action: 'First Pour: Spiral steadily up to 60% of total target water' },
      { time: '1:15 - 1:45', action: 'Second Pour: Gentle center pour to full water weight' },
      { time: '1:45 - 3:00', action: 'Drawdown: Give one gentle swirl; flat bed finish' }
    ]
  },
  chemex: {
    name: 'Chemex 6-Cup Wood Collar',
    ratio: 16.0,
    temp: '94°C (202°F)',
    grind: 'Medium-Coarse (kosher salt)',
    bloomTimeSec: 45,
    totalTimeSec: 240,
    instructions: [
      { time: '0:00 - 0:45', action: 'Bloom with 3x coffee weight; allow thick bonded filter to wet' },
      { time: '0:45 - 2:00', action: 'Continuous spiral pour keeping water 1cm below glass rim' },
      { time: '2:00 - 4:00', action: 'Complete final pour and allow clean glass drawdown' }
    ]
  },
  aeropress: {
    name: 'AeroPress Inverted Method',
    ratio: 13.0,
    temp: '88°C (190°F)',
    grind: 'Fine-Medium (sand)',
    bloomTimeSec: 30,
    totalTimeSec: 120,
    instructions: [
      { time: '0:00 - 0:30', action: 'Inverted cylinder: Add grounds and pour water swiftly' },
      { time: '0:30 - 1:00', action: 'Stir 4 times with paddle; attach rinsed filter cap' },
      { time: '1:00 - 2:00', action: 'Flip onto server and plunge gently for 30 seconds until hiss' }
    ]
  },
  frenchpress: {
    name: 'Artisan Glass French Press',
    ratio: 15.0,
    temp: '95°C (203°F)',
    grind: 'Coarse (sea salt)',
    bloomTimeSec: 60,
    totalTimeSec: 300,
    instructions: [
      { time: '0:00 - 1:00', action: 'Saturate all grounds completely and let crust form' },
      { time: '1:00 - 4:00', action: 'Break crust gently with spoon; skim white foam and fines' },
      { time: '4:00 - 5:00', action: 'Insert plunger to surface (do not press); pour slowly' }
    ]
  },
  coldbrew: {
    name: 'Slow Immersion Cold Mason',
    ratio: 8.0,
    temp: 'Room temp filtered / Chilled',
    grind: 'Extra Coarse (cracked pepper)',
    bloomTimeSec: 0,
    totalTimeSec: 720,
    instructions: [
      { time: '0 hr', action: 'Combine grounds and cold water gently in airtight vessel' },
      { time: '12 - 18 hr', action: 'Steep in cool ambient or refrigerator' },
      { time: 'Filter', action: 'Double-filter through unbleached paper; serve over clear ice' }
    ]
  }
};

export const OriginLabView: React.FC<OriginLabViewProps> = ({ onSelectProduct }) => {
  const [selectedDevice, setSelectedDevice] = useState<BrewDevice>('v60');
  const [doseGrams, setDoseGrams] = useState<number>(18);
  const [timerRunning, setTimerRunning] = useState(false);
  const [timerSeconds, setTimerSeconds] = useState(0);

  const device = BREW_CONFIGS[selectedDevice];
  const targetWater = Math.round(doseGrams * device.ratio);

  // Timer loop
  useEffect(() => {
    let interval: any = null;
    if (timerRunning) {
      interval = setInterval(() => {
        setTimerSeconds((prev) => prev + 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [timerRunning]);

  const resetTimer = () => {
    setTimerRunning(false);
    setTimerSeconds(0);
  };

  const timerMin = Math.floor(timerSeconds / 60);
  const timerSec = timerSeconds % 60;
  const timerFormatted = `${timerMin}:${timerSec < 10 ? '0' : ''}${timerSec}`;

  // Single origin products only
  const originProducts = COFFEE_PRODUCTS.filter(p => p.category === 'pourover' || p.category === 'whole-bean');

  return (
    <div className="max-w-6xl mx-auto px-4 py-8 space-y-12">
      
      {/* Intro Header */}
      <div className="text-center max-w-2xl mx-auto space-y-3">
        <div className="text-xs uppercase tracking-wider font-semibold text-[#88726C]">
          Direct Trade · Sourcing Transparency
        </div>
        <h1 className="font-heading text-3xl sm:text-4xl font-bold text-[#211A18] tracking-tight">
          Terroirs & Extraction Lab
        </h1>
        <p className="text-sm text-[#55433E] leading-relaxed">
          Every bean tells the story of its micro-climate, volcanic mineral soil, harvest altitude, and precise roasting curve. Explore our single lot producers and calibrate your cup.
        </p>
      </div>

      {/* Featured Micro-Lot Terroir Showcase */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h2 className="font-heading text-xl font-bold text-[#211A18]">
            Current Harvest Showcase
          </h2>
          <span className="text-xs text-[#795746]">
            Roasted on our Giesen 15kg Cast Iron Drum
          </span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {originProducts.map((coffee) => (
            <div
              key={coffee.id}
              onClick={() => onSelectProduct(coffee)}
              className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-2xl p-5 shadow-warm-ambient hover:shadow-lg transition-all cursor-pointer flex flex-col justify-between group"
            >
              <div>
                <div className="flex items-center justify-between mb-3">
                  <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#FAEDE8] text-[#94452C] border border-[#EFE8E1]">
                    {coffee.roastLevel} Roast
                  </span>
                  <span className="text-xs text-[#795746] font-mono">
                    {coffee.elevation || '1,800 MASL'}
                  </span>
                </div>

                <h3 className="font-heading text-lg font-bold text-[#211A18] group-hover:text-[#94452C] transition-colors">
                  {coffee.name}
                </h3>

                <p className="text-xs text-[#795746] mt-1 font-medium">
                  {coffee.origin}
                </p>

                {coffee.process && (
                  <div className="mt-2 text-xs text-[#55433E]">
                    <span className="font-semibold text-[#211A18]">Process: </span>
                    {coffee.process}
                  </div>
                )}

                {/* Sensory notes */}
                <div className="mt-3 pt-3 border-t border-[#EFE8E1]">
                  <span className="text-[11px] uppercase tracking-wider text-[#88726C] font-semibold block mb-1">
                    Tasting Profile
                  </span>
                  <div className="flex flex-wrap gap-1.5">
                    {coffee.notes.map((note, nIdx) => (
                      <span
                        key={nIdx}
                        className="text-xs px-2 py-0.5 rounded-md bg-[#FFF8F6] text-[#211A18] border border-[#EFE8E1]"
                      >
                        {note}
                      </span>
                    ))}
                  </div>
                </div>
              </div>

              <div className="mt-5 pt-3 border-t border-[#EFE8E1] flex items-center justify-between">
                <span className="font-heading font-bold text-base text-[#211A18] tabular-nums">
                  ${coffee.price.toFixed(2)}
                </span>
                <span className="text-xs font-semibold text-[#94452C] group-hover:underline">
                  Configure Recipe &rarr;
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Interactive Brew Calculator & Ratio Tool */}
      <div className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-3xl p-6 sm:p-10 shadow-warm-ambient">
        <div className="max-w-3xl mx-auto space-y-8">
          
          <div className="text-center space-y-2">
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FAEDE8] text-[#94452C] text-xs font-bold uppercase tracking-wider">
              <Sparkles className="w-3.5 h-3.5" />
              <span>Interactive Brewing Lab</span>
            </div>
            <h2 className="font-heading text-2xl sm:text-3xl font-bold text-[#211A18]">
              Precision Ratio & Pour Timer
            </h2>
            <p className="text-xs sm:text-sm text-[#55433E]">
              Calibrate your water weight, grind size, and extraction tempo for sweet balanced cups.
            </p>
          </div>

          {/* Device Selection Tabs */}
          <div className="flex flex-wrap items-center justify-center gap-2 p-1 bg-[#FFF8F6] border border-[#EFE8E1] rounded-2xl">
            {(Object.keys(BREW_CONFIGS) as BrewDevice[]).map((devKey) => (
              <button
                key={devKey}
                onClick={() => {
                  setSelectedDevice(devKey);
                  resetTimer();
                }}
                className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all ${
                  selectedDevice === devKey
                    ? 'bg-[#94452C] text-white shadow-xs'
                    : 'text-[#55433E] hover:text-[#211A18] hover:bg-[#FAEDE8]'
                }`}
              >
                {BREW_CONFIGS[devKey].name.split(' ')[0]}
              </button>
            ))}
          </div>

          {/* Dose Slider & Calculations */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            
            {/* Slider Column */}
            <div className="md:col-span-2 bg-[#FFF8F6] border border-[#EFE8E1] rounded-2xl p-5 space-y-4">
              <div className="flex justify-between items-center">
                <label className="text-xs font-bold uppercase tracking-wider text-[#211A18] flex items-center gap-1.5">
                  <Scale className="w-4 h-4 text-[#94452C]" />
                  <span>Dry Ground Coffee Dose</span>
                </label>
                <span className="font-heading font-extrabold text-2xl text-[#94452C] tabular-nums">
                  {doseGrams}g
                </span>
              </div>

              <input
                type="range"
                min="12"
                max="45"
                step="1"
                value={doseGrams}
                onChange={(e) => setDoseGrams(Number(e.target.value))}
                className="w-full h-2 bg-[#EFE8E1] rounded-lg appearance-none cursor-pointer accent-[#94452C]"
              />

              <div className="flex justify-between text-[11px] text-[#88726C] font-mono">
                <span>12g (Single Cup)</span>
                <span>20g (Standard Mug)</span>
                <span>45g (Server / Chemex)</span>
              </div>

              {/* Extraction Specs */}
              <div className="grid grid-cols-2 gap-3 pt-3 border-t border-[#EFE8E1] text-xs">
                <div>
                  <span className="text-[#88726C] block">Recommended Ratio:</span>
                  <span className="font-bold text-[#211A18]">1 : {device.ratio}</span>
                </div>
                <div>
                  <span className="text-[#88726C] block">Target Water Temp:</span>
                  <span className="font-bold text-[#211A18]">{device.temp}</span>
                </div>
                <div className="col-span-2">
                  <span className="text-[#88726C] block">Grind Calibration:</span>
                  <span className="font-medium text-[#55433E]">{device.grind}</span>
                </div>
              </div>
            </div>

            {/* Target Output Box */}
            <div className="bg-[#FAEDE8] border-2 border-[#94452C]/30 rounded-2xl p-6 text-center flex flex-col justify-center">
              <span className="text-xs uppercase tracking-wider font-semibold text-[#795746]">
                Target Water Weight
              </span>
              <div className="font-heading text-4xl sm:text-5xl font-extrabold text-[#94452C] my-2 tabular-nums">
                {targetWater}g
              </div>
              <span className="text-xs text-[#5C3D2E]">
                Yields approximately {Math.round(targetWater * 0.88)}ml liquid cup
              </span>
            </div>

          </div>

          {/* Interactive Extraction Timer & Stage Steps */}
          <div className="bg-[#FFF8F6] border border-[#EFE8E1] rounded-2xl p-6 space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#EFE8E1]">
              <div>
                <h3 className="font-heading font-bold text-lg text-[#211A18]">
                  {device.name} Pour Protocol
                </h3>
                <p className="text-xs text-[#795746]">
                  Target brew duration: ~{Math.floor(device.totalTimeSec / 60)} minutes
                </p>
              </div>

              {/* Stopwatch Display & Controls */}
              <div className="flex items-center gap-3">
                <div className="font-mono text-3xl font-bold text-[#211A18] tabular-nums px-4 py-1.5 bg-[#FFFFFF] border border-[#EFE8E1] rounded-xl shadow-xs">
                  {timerFormatted}
                </div>

                <button
                  onClick={() => setTimerRunning(!timerRunning)}
                  className={`w-11 h-11 rounded-full flex items-center justify-center text-white transition-all shadow-xs ${
                    timerRunning ? 'bg-[#5C3D2E] hover:bg-[#3D281E]' : 'bg-[#94452C] hover:bg-[#803B25]'
                  }`}
                  aria-label={timerRunning ? 'Pause timer' : 'Start timer'}
                >
                  {timerRunning ? <Pause className="w-5 h-5" /> : <Play className="w-5 h-5 ml-0.5" />}
                </button>

                <button
                  onClick={resetTimer}
                  className="w-11 h-11 rounded-full bg-[#FFFFFF] border border-[#EFE8E1] text-[#795746] hover:bg-[#FAEDE8] flex items-center justify-center transition-colors"
                  aria-label="Reset timer"
                >
                  <RotateCcw className="w-4 h-4" />
                </button>
              </div>
            </div>

            {/* Step-by-step instructions */}
            <div className="space-y-3">
              {device.instructions.map((step, sIdx) => (
                <div
                  key={sIdx}
                  className="flex items-start gap-4 p-3 bg-[#FFFFFF] border border-[#EFE8E1] rounded-xl text-xs sm:text-sm"
                >
                  <span className="font-mono font-bold text-[#94452C] bg-[#FAEDE8] px-2.5 py-1 rounded-md shrink-0 whitespace-nowrap">
                    {step.time}
                  </span>
                  <span className="text-[#211A18] font-medium leading-relaxed">
                    {step.action}
                  </span>
                </div>
              ))}
            </div>
          </div>

        </div>
      </div>

    </div>
  );
};
