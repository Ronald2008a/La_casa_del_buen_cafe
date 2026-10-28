import React, { useState } from 'react';
import { Product } from '../types/coffee';
import { Plus, Check, Leaf, AlertCircle, Sparkles } from 'lucide-react';

interface MenuCardProps {
  product: Product;
  onCustomize: (product: Product) => void;
  onQuickAdd: (product: Product) => void;
}

export const MenuCard: React.FC<MenuCardProps> = ({
  product,
  onCustomize,
  onQuickAdd
}) => {
  const [imageLoaded, setImageLoaded] = useState(false);
  const [imageError, setImageError] = useState(false);
  const [isAdding, setIsAdding] = useState(false);

  const handleQuickAddClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    setIsAdding(true);
    onQuickAdd(product);
    setTimeout(() => setIsAdding(false), 800);
  };

  return (
    <article
      onClick={() => onCustomize(product)}
      className="group relative bg-[#FFFFFF] border border-[#EFE8E1] rounded-2xl overflow-hidden shadow-warm-ambient hover:shadow-lg transition-all duration-300 flex flex-col cursor-pointer hover:-translate-y-0.5"
    >
      {/* Thumbnail with Asymmetric Top-Left Badge */}
      <div className="relative w-full aspect-[4/3] bg-[#FAEDE8] overflow-hidden">
        {!imageLoaded && !imageError && (
          <div className="absolute inset-0 bg-[#FAEDE8] animate-pulse flex items-center justify-center">
            <span className="text-xs text-[#88726C] font-mono">Loading roastery visual...</span>
          </div>
        )}

        {imageError ? (
          /* Styled Fallback container per zero-broken-image policy */
          <div className="w-full h-full bg-gradient-to-br from-[#FAEDE8] to-[#F4E5E1] flex flex-col items-center justify-center p-4 text-center">
            <div className="w-10 h-10 rounded-full bg-[#FFFFFF] shadow-sm flex items-center justify-center mb-2 text-[#94452C]">
              <Sparkles className="w-5 h-5" />
            </div>
            <span className="font-heading font-semibold text-sm text-[#211A18]">{product.name}</span>
            <span className="text-xs text-[#88726C] mt-1">{product.origin}</span>
          </div>
        ) : (
          <img
            src={product.image}
            alt={product.name}
            referrerPolicy="no-referrer"
            onLoad={() => setImageLoaded(true)}
            onError={() => setImageError(true)}
            className={`w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 ${
              imageLoaded ? 'opacity-100' : 'opacity-0'
            }`}
          />
        )}

        {/* Asymmetric Badge Floating Top-Left */}
        {product.badge && (
          <div className="absolute top-3 left-3 bg-[#1E1715]/85 backdrop-blur-md text-white text-[11px] font-semibold px-2.5 py-1 rounded-full uppercase tracking-wider shadow-sm">
            {product.badge}
          </div>
        )}

        {/* Roast level indicator for beans/brews */}
        {product.roastLevel && (
          <div className="absolute bottom-3 right-3 bg-[#FFF8F6]/90 backdrop-blur-md text-[#5C3D2E] text-[11px] font-medium px-2 py-0.5 rounded-full border border-[#EFE8E1]">
            {product.roastLevel} Roast
          </div>
        )}
      </div>

      {/* Card Body */}
      <div className="p-5 flex-1 flex flex-col justify-between">
        <div>
          {/* Unboxed clean metadata (no pill box) */}
          <div className="flex items-center gap-1.5 text-xs text-[#795746] mb-1.5 font-medium">
            <span>{product.origin.split(',')[0]}</span>
            {product.elevation && (
              <>
                <span aria-hidden="true">·</span>
                <span>{product.elevation}</span>
              </>
            )}
          </div>

          {/* Product Headline */}
          <h3 className="font-heading text-lg font-semibold text-[#211A18] tracking-tight group-hover:text-[#94452C] transition-colors leading-snug">
            {product.name}
          </h3>

          {/* Sensory Tasting Notes as quiet unboxed text */}
          <p className="mt-2 text-xs text-[#55433E] line-clamp-1">
            <span className="font-medium text-[#211A18]">Notes: </span>
            {product.notes.join(' · ')}
          </p>

          <p className="mt-2 text-xs text-[#795746] line-clamp-2 leading-relaxed">
            {product.description}
          </p>

          {/* Allergen & Dietary Chips matching spec exactly:
              Matcha green tint (#EDF2EE bg, #4E6E58 text) for organic/oat/dairy-free
              Roast brown tint (#F4EFEB bg, #5C3D2E text) for gluten/nuts/caffeine */}
          <div className="mt-3.5 flex flex-wrap gap-1.5">
            {product.allergenTags.map((tag, idx) => (
              <span
                key={idx}
                className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide ${
                  tag.type === 'matcha'
                    ? 'bg-[#EDF2EE] text-[#4E6E58]'
                    : 'bg-[#F4EFEB] text-[#5C3D2E]'
                }`}
              >
                {tag.type === 'matcha' && <Leaf className="w-3 h-3 text-[#4E6E58]" />}
                {tag.type === 'roast' && <AlertCircle className="w-3 h-3 text-[#5C3D2E]" />}
                <span>{tag.label}</span>
              </span>
            ))}
          </div>
        </div>

        {/* Card Footer: Price & Primary Button */}
        <div className="mt-5 pt-3.5 border-t border-[#EFE8E1] flex items-center justify-between gap-3">
          <div className="flex flex-col">
            <span className="text-[11px] uppercase tracking-wider text-[#88726C] font-semibold">
              Price
            </span>
            <span className="font-heading text-xl font-bold text-[#211A18] tabular-nums">
              ${product.price.toFixed(2)}
            </span>
          </div>

          <div className="flex items-center gap-2">
            {/* Quick Add icon pill */}
            <button
              onClick={handleQuickAddClick}
              title="Quick Add with default recipe"
              className={`w-10 h-10 rounded-full flex items-center justify-center border transition-all duration-200 ${
                isAdding
                  ? 'bg-[#4E6E58] text-white border-[#4E6E58]'
                  : 'bg-[#FFF8F6] text-[#94452C] border-[#EFE8E1] hover:bg-[#FAEDE8] hover:border-[#94452C]'
              }`}
            >
              {isAdding ? <Check className="w-4 h-4" /> : <Plus className="w-4 h-4" />}
            </button>

            {/* Main Customize Pill Button */}
            <button
              onClick={(e) => {
                e.stopPropagation();
                onCustomize(product);
              }}
              className="px-4 py-2 text-xs sm:text-sm font-semibold rounded-full bg-[#94452C] text-white hover:bg-[#803B25] active:scale-[0.98] transition-all shadow-sm"
            >
              Customize
            </button>
          </div>
        </div>
      </div>
    </article>
  );
};
