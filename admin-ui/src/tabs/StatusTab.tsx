import { Button, Notice, Spinner } from "@wordpress/components";
import { useEffect, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

import {
  errorMessage,
  getStatus,
  testAuthHeader,
  testMcpConnection,
} from "../api";
import { Section } from "../components/Section";
import type { CheckStatus, Status, TestResult } from "../types";

const STATUS_TEXT: Record<CheckStatus, string> = {
  ok: __("OK", "mcp-builder"),
  warning: __("Warning", "mcp-builder"),
  error: __("Problem", "mcp-builder"),
  inactive: __("Not active", "mcp-builder"),
};

const NOTICE_STATUS = {
  ok: "success",
  error: "error",
  unknown: "warning",
} as const;

const TestButton = ({
  label,
  run,
}: {
  label: string;
  run: () => Promise<TestResult>;
}) => {
  const [busy, setBusy] = useState(false);
  const [result, setResult] = useState<TestResult | null>(null);

  const onClick = async () => {
    setBusy(true);
    setResult(await run());
    setBusy(false);
  };

  return (
    <div className="mcpb-test">
      <Button
        variant="secondary"
        onClick={onClick}
        isBusy={busy}
        disabled={busy}
      >
        {label}
      </Button>
      <div aria-live="polite">
        {result && (
          <Notice status={NOTICE_STATUS[result.status]} isDismissible={false}>
            {result.message}
          </Notice>
        )}
      </div>
    </div>
  );
};

export const StatusTab = () => {
  const [status, setStatus] = useState<Status | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getStatus()
      .then(setStatus)
      .catch((e) => setError(errorMessage(e)));
  }, []);

  if (error) {
    return (
      <Notice status="error" isDismissible={false}>
        {error}
      </Notice>
    );
  }
  if (!status) {
    return <Spinner />;
  }

  return (
    <>
      <Section
        title={__("Connection tests", "mcp-builder")}
        description={__(
          "Run these after setup, and whenever an AI client cannot connect.",
          "mcp-builder",
        )}
      >
        <TestButton
          label={__("Test MCP connection", "mcp-builder")}
          run={testMcpConnection}
        />
        <TestButton
          label={__("Test Authorization header", "mcp-builder")}
          run={() =>
            testAuthHeader().catch((e) => ({
              status: "error",
              message: errorMessage(e),
            }))
          }
        />
      </Section>

      <Section title={__("Environment", "mcp-builder")}>
        <table className="widefat striped mcpb-table">
          <thead>
            <tr>
              <th scope="col">{__("Check", "mcp-builder")}</th>
              <th scope="col">{__("Status", "mcp-builder")}</th>
              <th scope="col">{__("Current", "mcp-builder")}</th>
              <th scope="col">{__("Required", "mcp-builder")}</th>
              <th scope="col">{__("Notes", "mcp-builder")}</th>
            </tr>
          </thead>
          <tbody>
            {status.checks.map((check) => (
              <tr key={check.id}>
                <td>{check.label}</td>
                <td>
                  <span className={`mcpb-badge mcpb-badge--${check.status}`}>
                    {STATUS_TEXT[check.status]}
                  </span>
                </td>
                <td>{check.current ?? "—"}</td>
                <td>{check.required ? `${check.required}+` : "—"}</td>
                <td>{check.message}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </Section>

      <Section
        title={__("Tools", "mcp-builder")}
        description={__(
          "Tools for builders that are not active are hidden from AI clients.",
          "mcp-builder",
        )}
      >
        <ul className="mcpb-tool-list">
          {status.tools.map((tool) => (
            <li key={tool.name}>
              <code>{tool.name}</code> {tool.label}{" "}
              <span className="mcpb-badge">
                {tool.readonly
                  ? __("Read", "mcp-builder")
                  : __("Write", "mcp-builder")}
              </span>
              {!tool.available && (
                <span className="mcpb-badge mcpb-badge--inactive">
                  {__("Hidden", "mcp-builder")}
                </span>
              )}
            </li>
          ))}
        </ul>
      </Section>
    </>
  );
};
