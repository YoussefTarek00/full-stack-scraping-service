import { Product } from "@/lib/api";

export function ProductCard({ product }: { product: Product }) {
  return (
    <div className="flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <div className="flex aspect-[3/4] w-full items-center justify-center bg-zinc-100 p-4 dark:bg-zinc-800">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={product.image_url}
          alt={product.title}
          loading="lazy"
          className="max-h-full max-w-full object-contain"
        />
      </div>
      <div className="flex flex-1 flex-col gap-1 p-4">
        <h3 className="line-clamp-2 text-sm font-medium text-zinc-900 dark:text-zinc-50">
          {product.title}
        </h3>
        <p className="mt-auto text-lg font-semibold text-zinc-900 dark:text-zinc-50">
          £{product.price}
        </p>
      </div>
    </div>
  );
}
