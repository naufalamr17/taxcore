import * as React from "react";
import { cn } from "@/lib/utils";
import { jsx as _jsx } from "react/jsx-runtime";
const Input = React.forwardRef(({ className, type, ...props }, ref) => {
	return /* @__PURE__ */ _jsx("input", {
		type,
		className: cn("flex h-9 w-full rounded-xl border border-input bg-muted/60 px-3 py-1 text-base transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/30 disabled:cursor-not-allowed disabled:opacity-50 md:text-xs", className),
		ref,
		...props
	});
});
Input.displayName = "Input";
export { Input };
