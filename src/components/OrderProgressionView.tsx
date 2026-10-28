import React, { useState, useEffect } from 'react';
import { LiveOrder, OrderStage } from '../types/coffee';
import { 
  Clock, 
  Coffee, 
  CheckCircle2, 
  MapPin, 
  QrCode, 
  ChevronRight, 
  Sparkles, 
  RotateCcw, 
  Receipt,
  Share2,
  Bell
} from 'lucide-react';

interface OrderProgressionViewProps {
  order: LiveOrder;
  onUpdateStage: (newStage: OrderStage) => void;
  onNewOrder: () => void;
}

export const OrderProgressionView: React.FC<OrderProgressionViewProps> = ({
  order,
  onUpdateStage,
  onNewOrder
}) => {
  const [activeStage, setActiveStage] = useState<OrderStage>(order.stage);
  const [secondsRemaining, setSecondsRemaining] = useState<number>(240); // 4 minutes
  const [showQrModal, setShowQrModal] = useState(false);

  // Sync with prop
  useEffect(() => {
    setActiveStage(order.stage);
  }, [order.stage]);

  // Countdown timer when brewing
  useEffect(() => {
    if (activeStage !== 'brewing') return;
    const interval = setInterval(() => {
      setSecondsRemaining((prev) => {
        if (prev <= 1) {
          setActiveStage('ready');
          onUpdateStage('ready');
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(interval);
  }, [activeStage, onUpdateStage]);

  const handleStageSelect = (stage: OrderStage) => {
    setActiveStage(stage);
    onUpdateStage(stage);
  };

  const minutes = Math.floor(secondsRemaining / 60);
  const seconds = secondsRemaining % 60;
  const timeFormatted = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

  return (
    <div className="max-w-2xl mx-auto px-4 py-6 sm:py-10 space-y-6">
      
      {/* Top Banner & Stage Interactive Switcher */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#EFE8E1]">
        <div>
          <div className="text-xs uppercase tracking-wider font-semibold text-[#88726C]">
            Live Roastery Tracker
          </div>
          <h1 className="font-heading text-2xl sm:text-3xl font-bold text-[#211A18] tracking-tight">
            Order Fulfillment
          </h1>
        </div>

        {/* Stage Toggle Simulator (allows instant testing of all 3 required states) */}
        <div className="flex items-center gap-1 p-1 bg-[#FAEDE8] border border-[#EFE8E1] rounded-full self-start sm:self-auto">
          <button
            onClick={() => handleStageSelect('received')}
            className={`px-3 py-1 text-xs font-semibold rounded-full transition-all ${
              activeStage === 'received'
                ? 'bg-[#5C3D2E] text-white shadow-xs'
                : 'text-[#795746] hover:text-[#211A18]'
            }`}
          >
            1. Recibido
          </button>
          <button
            onClick={() => handleStageSelect('brewing')}
            className={`px-3 py-1 text-xs font-semibold rounded-full transition-all ${
              activeStage === 'brewing'
                ? 'bg-[#94452C] text-white shadow-xs'
                : 'text-[#795746] hover:text-[#211A18]'
            }`}
          >
            2. En Preparación
          </button>
          <button
            onClick={() => handleStageSelect('ready')}
            className={`px-3 py-1 text-xs font-semibold rounded-full transition-all ${
              activeStage === 'ready'
                ? 'bg-[#4E6E58] text-white shadow-xs'
                : 'text-[#795746] hover:text-[#211A18]'
            }`}
          >
            3. Listo
          </button>
        </div>
      </div>

      {/* Primary Status Presentation Block (Embodying Design Specification directly) */}
      <div className="transition-all duration-300">
        
        {/* STATE 1: RECIBIDO (RECEIVED) */}
        {activeStage === 'received' && (
          <div className="bg-[#FFFFFF] border-2 border-[#5C3D2E] rounded-3xl p-6 sm:p-8 shadow-warm-ambient text-center relative overflow-hidden">
            <div className="w-16 h-16 rounded-full bg-[#F4EFEB] border border-[#5C3D2E]/20 mx-auto flex items-center justify-center text-[#5C3D2E] mb-4">
              <Clock className="w-8 h-8" />
            </div>

            <div className="inline-block px-3 py-1 rounded-full bg-[#F4EFEB] text-[#5C3D2E] text-xs font-bold uppercase tracking-wider mb-2">
              Recibido · Queued
            </div>

            <h2 className="font-heading text-2xl sm:text-3xl font-bold text-[#211A18]">
              Order Ticket Confirmed
            </h2>

            <p className="text-sm text-[#55433E] max-w-md mx-auto mt-2 leading-relaxed">
              Your ticket <span className="font-mono font-bold text-[#211A18]">{order.orderId}</span> has entered our espresso bar baristas' queue. Lucas is calibrating the roast dose.
            </p>

            {/* Step Timeline Indicator */}
            <div className="mt-8 pt-6 border-t border-[#EFE8E1] flex items-center justify-between text-xs text-[#795746] max-w-sm mx-auto">
              <div className="flex flex-col items-center">
                <span className="w-3 h-3 rounded-full bg-[#5C3D2E]" />
                <span className="mt-1 font-semibold text-[#5C3D2E]">Recibido</span>
              </div>
              <div className="h-0.5 flex-1 bg-[#EFE8E1] mx-2" />
              <div className="flex flex-col items-center opacity-40">
                <span className="w-3 h-3 rounded-full bg-[#dbc1ba]" />
                <span className="mt-1">Preparación</span>
              </div>
              <div className="h-0.5 flex-1 bg-[#EFE8E1] mx-2" />
              <div className="flex flex-col items-center opacity-40">
                <span className="w-3 h-3 rounded-full bg-[#dbc1ba]" />
                <span className="mt-1">Listo</span>
              </div>
            </div>

            <button
              onClick={() => handleStageSelect('brewing')}
              className="mt-6 inline-flex items-center gap-2 text-xs font-semibold text-[#94452C] hover:text-[#5C3D2E] underline underline-offset-4"
            >
              <span>Simulate Barista Beginning Extraction &rarr;</span>
            </button>
          </div>
        )}

        {/* STATE 2: EN PREPARACIÓN (BREWING) */}
        {activeStage === 'brewing' && (
          <div className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-3xl p-6 sm:p-8 shadow-warm-ambient relative overflow-hidden">
            {/* Terracotta Top Accent Strip */}
            <div className="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#94452C] via-[#C86D51] to-[#94452C]" />

            <div className="flex flex-col items-center text-center">
              {/* Steaming Coffee Icon with Terracotta Pulsed Animation */}
              <div className="relative mb-4">
                <div className="w-20 h-20 rounded-full bg-[#FAEDE8] border-2 border-[#94452C] flex items-center justify-center text-[#94452C] relative z-10 animate-pulse">
                  <Coffee className="w-10 h-10" />
                </div>
                {/* Simulated steam wisps */}
                <div className="absolute -top-3 left-1/2 -translate-x-1/2 flex gap-1 z-0">
                  <div className="w-1 h-3 bg-[#94452C]/60 rounded-full animate-steam" style={{ animationDelay: '0s' }} />
                  <div className="w-1 h-4 bg-[#94452C]/60 rounded-full animate-steam" style={{ animationDelay: '0.4s' }} />
                  <div className="w-1 h-3 bg-[#94452C]/60 rounded-full animate-steam" style={{ animationDelay: '0.8s' }} />
                </div>
              </div>

              <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FAEDE8] text-[#94452C] text-xs font-bold uppercase tracking-wider mb-2 border border-[#EFE8E1]">
                <span className="w-2 h-2 rounded-full bg-[#94452C] animate-ping" />
                <span>En Preparación · Hand-Crafting</span>
              </div>

              <h2 className="font-heading text-2xl sm:text-3xl font-bold text-[#211A18]">
                Crafting Your Order
              </h2>

              <p className="text-sm text-[#55433E] max-w-md mt-2 leading-relaxed">
                {order.baristaNote}
              </p>

              {/* Live countdown timer badge */}
              <div className="mt-4 px-4 py-2 bg-[#FFF8F6] border border-[#EFE8E1] rounded-full inline-flex items-center gap-2">
                <Clock className="w-4 h-4 text-[#94452C]" />
                <span className="text-xs text-[#55433E]">
                  Estimated ready in <strong className="font-mono text-sm text-[#211A18] font-bold">{timeFormatted}</strong>
                </span>
              </div>

              {/* Active timeline bar filled with Terracotta Amber #94452C / #C86D51 */}
              <div className="w-full max-w-md mt-8">
                <div className="flex justify-between text-xs font-semibold mb-2">
                  <span className="text-[#5C3D2E]">Recibido</span>
                  <span className="text-[#94452C] font-bold">En Preparación (70%)</span>
                  <span className="text-[#88726C]">Listo para Recoger</span>
                </div>
                <div className="h-2.5 w-full bg-[#FAEDE8] rounded-full overflow-hidden border border-[#EFE8E1]">
                  <div 
                    className="h-full bg-gradient-to-r from-[#94452C] to-[#C86D51] rounded-full transition-all duration-1000 ease-out"
                    style={{ width: '70%' }}
                  />
                </div>
              </div>

              <button
                onClick={() => handleStageSelect('ready')}
                className="mt-6 inline-flex items-center gap-2 text-xs font-semibold text-[#4E6E58] hover:text-[#211A18] underline underline-offset-4"
              >
                <span>Simulate Barista Ringing Order Ready &rarr;</span>
              </button>
            </div>
          </div>
        )}

        {/* STATE 3: LISTO PARA RECOGER (READY FOR PICKUP) */}
        {activeStage === 'ready' && (
          <div className="bg-[#4E6E58] text-white rounded-3xl p-6 sm:p-8 shadow-warm-ambient relative overflow-hidden text-center">
            
            {/* Herbal Matcha Green solid background with large Outfit Bold display text */}
            <div className="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md mx-auto flex items-center justify-center text-white mb-4">
              <CheckCircle2 className="w-10 h-10 text-white" />
            </div>

            <div className="inline-block px-3.5 py-1 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider mb-2">
              Listo para Recoger · Ready for Pickup
            </div>

            <h2 className="text-sm font-medium text-white/90">
              Please collect your fresh brew at
            </h2>

            {/* Store pickup counter in large Outfit Bold display text per spec */}
            <div className="font-heading text-4xl sm:text-5xl font-extrabold tracking-tight text-white my-3">
              {order.pickupCounter}
            </div>

            <p className="text-xs sm:text-sm text-white/90 max-w-md mx-auto leading-relaxed">
              Prepared for <strong className="text-white underline">{order.customerName}</strong>. Show this screen or present PIN <strong className="font-mono text-base bg-white/20 px-2 py-0.5 rounded">{order.lockerPin}</strong> to our pickup barista.
            </p>

            {/* QR Scan Button */}
            <div className="mt-6 pt-6 border-t border-white/20 flex flex-wrap items-center justify-center gap-3">
              <button
                onClick={() => setShowQrModal(true)}
                className="px-5 py-2.5 rounded-full bg-white text-[#4E6E58] font-bold text-xs sm:text-sm hover:bg-[#F6FFF5] transition-all flex items-center gap-2 shadow-sm"
              >
                <QrCode className="w-4 h-4" />
                <span>Show Barista QR Pass</span>
              </button>

              <button
                onClick={() => handleStageSelect('brewing')}
                className="px-4 py-2.5 rounded-full bg-white/10 text-white font-medium text-xs hover:bg-white/20 transition-all flex items-center gap-1.5"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                <span>Replay Brewing Stage</span>
              </button>
            </div>
          </div>
        )}

      </div>

      {/* Pickup Station & Roastery Details Card */}
      <div className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-2xl p-5 shadow-warm-ambient flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div className="flex gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-[#FAEDE8] text-[#94452C] flex items-center justify-center shrink-0">
            <MapPin className="w-5 h-5" />
          </div>
          <div>
            <h3 className="font-heading font-bold text-base text-[#211A18]">
              Downtown Roastery Lab
            </h3>
            <p className="text-xs text-[#55433E] mt-0.5">
              428 Artisan Alley, Station B · Counter Shelf #04
            </p>
            <p className="text-[11px] text-[#795746] mt-1">
              Barista on duty: <strong className="text-[#211A18] font-semibold">{order.baristaName}</strong>
            </p>
          </div>
        </div>

        <button
          onClick={() => setShowQrModal(true)}
          className="self-stretch sm:self-auto px-4 py-2 text-xs font-semibold rounded-full border border-[#EFE8E1] bg-[#FFF8F6] hover:bg-[#FAEDE8] text-[#211A18] flex items-center justify-center gap-2 transition-colors"
        >
          <QrCode className="w-4 h-4 text-[#94452C]" />
          <span>Pickup Code</span>
        </button>
      </div>

      {/* Itemized Order Receipt Card */}
      <div className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-2xl p-6 shadow-warm-ambient space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-[#EFE8E1]">
          <div className="flex items-center gap-2">
            <Receipt className="w-4 h-4 text-[#94452C]" />
            <h3 className="font-heading font-bold text-sm text-[#211A18]">
              Itemized Receipt ({order.orderId})
            </h3>
          </div>
          <span className="text-xs text-[#88726C] font-mono">
            {order.placedTime}
          </span>
        </div>

        {/* Ordered items */}
        <div className="space-y-3 divide-y divide-[#EFE8E1]">
          {order.items.map((item, idx) => (
            <div key={idx} className="pt-3 first:pt-0 flex items-start justify-between gap-3">
              <div className="flex gap-3">
                <span className="w-6 h-6 rounded-md bg-[#FAEDE8] text-[#94452C] font-mono text-xs font-bold flex items-center justify-center shrink-0">
                  {item.quantity}×
                </span>
                <div>
                  <h4 className="font-heading font-bold text-sm text-[#211A18]">
                    {item.product.name}
                  </h4>
                  <p className="text-xs text-[#795746] mt-0.5">
                    {item.product.isDrink && `${item.customization.size} · ${item.customization.milk} · ${item.customization.temp}`}
                    {item.customization.syrup !== 'None' && ` · ${item.customization.syrup.replace(' (+0.75)', '')}`}
                  </p>
                  {item.customization.notes && (
                    <p className="text-[11px] text-[#94452C] italic mt-0.5">
                      Note: {item.customization.notes}
                    </p>
                  )}
                </div>
              </div>

              <span className="font-heading font-bold text-sm text-[#211A18] tabular-nums">
                ${item.totalPrice.toFixed(2)}
              </span>
            </div>
          ))}
        </div>

        {/* Financial breakdown */}
        <div className="pt-3 border-t border-[#EFE8E1] space-y-1.5 text-xs text-[#55433E]">
          <div className="flex justify-between">
            <span>Subtotal</span>
            <span className="tabular-nums font-mono text-[#211A18]">${order.subtotal.toFixed(2)}</span>
          </div>
          <div className="flex justify-between">
            <span>Tax (8.75%)</span>
            <span className="tabular-nums font-mono text-[#211A18]">${order.tax.toFixed(2)}</span>
          </div>
          <div className="flex justify-between">
            <span>Barista Gratuity</span>
            <span className="tabular-nums font-mono text-[#211A18]">${order.tip.toFixed(2)}</span>
          </div>
          <div className="pt-2 border-t border-[#EFE8E1] flex justify-between text-sm font-bold text-[#211A18]">
            <span>Total Paid</span>
            <span className="font-heading text-lg font-bold text-[#94452C] tabular-nums">
              ${order.total.toFixed(2)}
            </span>
          </div>
        </div>

        {/* Bottom Actions */}
        <div className="pt-4 border-t border-[#EFE8E1] flex flex-col sm:flex-row gap-3">
          <button
            onClick={onNewOrder}
            className="flex-1 h-12 rounded-full bg-[#94452C] hover:bg-[#803B25] text-white font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all shadow-xs"
          >
            <Coffee className="w-4 h-4" />
            <span>Order Another Artisan Brew</span>
          </button>
        </div>
      </div>

      {/* QR Pass Modal */}
      {showQrModal && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-3xl p-6 sm:p-8 max-w-sm w-full text-center space-y-4 shadow-2xl animate-in zoom-in-95 duration-150">
            <h3 className="font-heading text-xl font-bold text-[#211A18]">
              Contactless Pickup Pass
            </h3>
            <p className="text-xs text-[#55433E]">
              Present this pass at <strong className="text-[#94452C]">{order.pickupCounter}</strong>
            </p>

            <div className="bg-[#FFF8F6] p-6 rounded-2xl border border-[#EFE8E1] inline-block">
              {/* Simulated crisp geometric QR Code */}
              <div className="w-48 h-48 bg-white border border-[#211A18] p-3 flex flex-col justify-between mx-auto">
                <div className="flex justify-between">
                  <div className="w-12 h-12 border-4 border-[#211A18] p-1 flex items-center justify-center">
                    <div className="w-4 h-4 bg-[#211A18]" />
                  </div>
                  <div className="w-12 h-12 border-4 border-[#211A18] p-1 flex items-center justify-center">
                    <div className="w-4 h-4 bg-[#211A18]" />
                  </div>
                </div>
                <div className="text-center font-mono text-[10px] text-[#211A18] tracking-widest my-auto">
                  {order.orderId}
                  <div className="text-xs font-bold text-[#94452C]">PIN: {order.lockerPin}</div>
                </div>
                <div className="flex justify-between">
                  <div className="w-12 h-12 border-4 border-[#211A18] p-1 flex items-center justify-center">
                    <div className="w-4 h-4 bg-[#211A18]" />
                  </div>
                  <div className="w-6 h-6 bg-[#211A18]" />
                </div>
              </div>
            </div>

            <div className="text-xs text-[#88726C]">
              Order #{order.orderId} · {order.customerName}
            </div>

            <button
              onClick={() => setShowQrModal(false)}
              className="w-full py-3 rounded-full bg-[#211A18] text-white font-semibold text-xs transition-colors hover:bg-black"
            >
              Done / Close
            </button>
          </div>
        </div>
      )}

    </div>
  );
};
