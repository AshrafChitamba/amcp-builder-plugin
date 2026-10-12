import { Button } from "@wordpress/components";
import { useCopyToClipboard } from "@wordpress/compose";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

interface Props {
  text: string;
  label?: string;
}

export const CopyButton = ({ text, label }: Props) => {
  const [copied, setCopied] = useState(false);
  const ref = useCopyToClipboard(text, () => {
    setCopied(true);
    window.setTimeout(() => setCopied(false), 2000);
  });

  return (
    <Button variant="secondary" size="compact" ref={ref}>
      {copied
        ? __("Copied", "mcp-builder")
        : (label ?? __("Copy", "mcp-builder"))}
    </Button>
  );
};
