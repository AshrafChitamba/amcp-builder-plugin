import {
  Button,
  Notice,
  SelectControl,
  Spinner,
  TextControl,
  ToggleControl,
} from "@wordpress/components";
import { useCallback, useEffect, useState } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";

import { errorMessage, getAudit, getSnapshots, restoreSnapshot } from "../api";
import { Section } from "../components/Section";
import { useSettings } from "../hooks/useSettings";
import type { AuditFilters, AuditPage, Settings, Snapshot } from "../types";

const PER_PAGE = 25;

type NumberKey =
  | "rate_limit_reads"
  | "rate_limit_writes"
  | "snapshot_keep"
  | "snapshot_days"
  | "audit_days";

const NUMBER_FIELDS: Array<{ key: NumberKey; label: string; help: string }> = [
  {
    key: "rate_limit_reads",
    label: __("Reads per minute (per user)", "mcp-builder"),
    help: __("Stops runaway AI loops.", "mcp-builder"),
  },
  {
    key: "rate_limit_writes",
    label: __("Changes per minute (per user)", "mcp-builder"),
    help: "",
  },
  {
    key: "snapshot_keep",
    label: __("Snapshots kept per page", "mcp-builder"),
    help: __("Older ones are deleted daily.", "mcp-builder"),
  },
  {
    key: "snapshot_days",
    label: __("Keep snapshots for (days)", "mcp-builder"),
    help: "",
  },
  {
    key: "audit_days",
    label: __("Keep audit log for (days)", "mcp-builder"),
    help: "",
  },
];

const formatDate = (value: string): string => new Date(value).toLocaleString();

const confirmRestore = (id: number): boolean =>
  // eslint-disable-next-line no-alert
  window.confirm(
    sprintf(
      /* translators: %d: snapshot ID. */
      __(
        "Restore snapshot #%d? Changes made after it are undone. The current state is snapshotted first, so you can undo this.",
        "mcp-builder",
      ),
      id,
    ),
  );

const SettingsForm = ({
  settings,
  save,
  saving,
}: {
  settings: Settings;
  save: (patch: Partial<Settings>) => Promise<boolean>;
  saving: boolean;
}) => {
  const [draft, setDraft] = useState(settings);
  const [saved, setSaved] = useState(false);

  const toggleKill = (value: boolean) => {
    if (
      value &&
      // eslint-disable-next-line no-alert
      !window.confirm(
        __(
          "Turn on the kill switch? Every AI client is cut off until you turn it off again.",
          "mcp-builder",
        ),
      )
    ) {
      return;
    }
    save({ kill_switch: value }).then(() =>
      setDraft((current) => ({ ...current, kill_switch: value })),
    );
  };

  const submit = async () => {
    setSaved(false);
    setSaved(await save(draft));
  };

  return (
    <>
      <Section title={__("Kill switch", "mcp-builder")}>
        <ToggleControl
          __nextHasNoMarginBottom
          label={__("Switch off MCP Builder", "mcp-builder")}
          help={__(
            "Immediately refuses every AI request, including official Bricks and Elementor tools. Use it if something goes wrong.",
            "mcp-builder",
          )}
          checked={settings.kill_switch}
          disabled={saving}
          onChange={toggleKill}
        />
      </Section>

      <Section title={__("Defaults and limits", "mcp-builder")}>
        <ToggleControl
          __nextHasNoMarginBottom
          label={__("Preview changes by default (dry run)", "mcp-builder")}
          help={__(
            "Changes are only previewed unless the AI explicitly asks to save.",
            "mcp-builder",
          )}
          checked={draft.dry_run_default}
          onChange={(value) => setDraft({ ...draft, dry_run_default: value })}
        />
        <div className="mcpb-spacer" />
        <SelectControl
          __next40pxDefaultSize
          __nextHasNoMarginBottom
          label={__(
            "When someone has the page open in an editor",
            "mcp-builder",
          )}
          value={draft.post_lock}
          options={[
            {
              value: "deny",
              label: __("Refuse AI changes (recommended)", "mcp-builder"),
            },
            {
              value: "allow",
              label: __("Allow AI changes anyway", "mcp-builder"),
            },
          ]}
          onChange={(value) =>
            setDraft({
              ...draft,
              post_lock: value as Settings["post_lock"],
            })
          }
        />
        <div className="mcpb-grid">
          {NUMBER_FIELDS.map((field) => (
            <TextControl
              key={field.key}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              type="number"
              min={1}
              label={field.label}
              help={field.help}
              value={String(draft[field.key])}
              onChange={(value) =>
                setDraft({
                  ...draft,
                  [field.key]: Number(value),
                })
              }
            />
          ))}
        </div>
        <div className="mcpb-actions">
          <Button
            variant="primary"
            onClick={submit}
            isBusy={saving}
            disabled={saving}
          >
            {__("Save changes", "mcp-builder")}
          </Button>
          <span aria-live="polite">{saved && __("Saved.", "mcp-builder")}</span>
        </div>
      </Section>
    </>
  );
};

const AuditLog = ({ onRestored }: { onRestored: () => void }) => {
  const [filters, setFilters] = useState<AuditFilters>({
    outcome: "",
    ability: "",
    post_id: 0,
  });
  const [page, setPage] = useState(1);
  const [data, setData] = useState<AuditPage | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    getAudit(filters, page, PER_PAGE)
      .then(setData)
      .catch((e) => setError(errorMessage(e)));
  }, [filters, page]);

  useEffect(load, [load]);

  const restore = async (id: number) => {
    if (!confirmRestore(id)) {
      return;
    }
    try {
      await restoreSnapshot(id);
      load();
      onRestored();
    } catch (e) {
      setError(errorMessage(e));
    }
  };

  const pages = data ? Math.max(1, Math.ceil(data.total / PER_PAGE)) : 1;
  const setFilter = (patch: Partial<AuditFilters>) => {
    setPage(1);
    setFilters({ ...filters, ...patch });
  };

  return (
    <Section
      title={__("Audit log", "mcp-builder")}
      description={__(
        "Every change, refusal and error made through MCP. Successful reads are not logged.",
        "mcp-builder",
      )}
    >
      {error && (
        <Notice status="error" onRemove={() => setError(null)}>
          {error}
        </Notice>
      )}
      <div className="mcpb-filters">
        <SelectControl
          __next40pxDefaultSize
          __nextHasNoMarginBottom
          label={__("Outcome", "mcp-builder")}
          value={filters.outcome}
          options={[
            { value: "", label: __("All", "mcp-builder") },
            { value: "ok", label: __("Saved", "mcp-builder") },
            {
              value: "dry-run",
              label: __("Preview", "mcp-builder"),
            },
            {
              value: "denied",
              label: __("Refused", "mcp-builder"),
            },
            { value: "error", label: __("Error", "mcp-builder") },
          ]}
          onChange={(value) =>
            setFilter({
              outcome: value as AuditFilters["outcome"],
            })
          }
        />
        <TextControl
          __next40pxDefaultSize
          __nextHasNoMarginBottom
          label={__("Tool", "mcp-builder")}
          placeholder="mcp-builder/bricks-add-elements"
          value={filters.ability}
          onChange={(value) => setFilter({ ability: value.trim() })}
        />
        <TextControl
          __next40pxDefaultSize
          __nextHasNoMarginBottom
          type="number"
          label={__("Page ID", "mcp-builder")}
          value={filters.post_id ? String(filters.post_id) : ""}
          onChange={(value) => setFilter({ post_id: Number(value) || 0 })}
        />
      </div>

      {!data ? (
        <Spinner />
      ) : (
        <>
          <table className="widefat striped mcpb-table">
            <thead>
              <tr>
                <th scope="col">{__("When", "mcp-builder")}</th>
                <th scope="col">{__("User", "mcp-builder")}</th>
                <th scope="col">{__("Tool", "mcp-builder")}</th>
                <th scope="col">{__("Outcome", "mcp-builder")}</th>
                <th scope="col">{__("Page", "mcp-builder")}</th>
                <th scope="col">{__("Details", "mcp-builder")}</th>
              </tr>
            </thead>
            <tbody>
              {data.items.length === 0 && (
                <tr>
                  <td colSpan={6}>
                    {__("Nothing logged yet.", "mcp-builder")}
                  </td>
                </tr>
              )}
              {data.items.map((entry) => (
                <tr key={entry.id}>
                  <td>{formatDate(entry.createdAt)}</td>
                  <td>{entry.user || "—"}</td>
                  <td>
                    <code>{entry.ability}</code>
                  </td>
                  <td>
                    <span
                      className={`mcpb-badge mcpb-badge--outcome-${entry.outcome}`}
                    >
                      {entry.outcome}
                    </span>
                  </td>
                  <td>{entry.postId || "—"}</td>
                  <td>
                    {entry.message && (
                      <p className="mcpb-message">{entry.message}</p>
                    )}
                    {entry.inputSummary && (
                      <details>
                        <summary>{__("Input", "mcp-builder")}</summary>
                        <code className="mcpb-input">{entry.inputSummary}</code>
                      </details>
                    )}
                    {entry.snapshotId > 0 && (
                      <Button
                        variant="link"
                        onClick={() => restore(entry.snapshotId)}
                      >
                        {sprintf(
                          /* translators: %d: snapshot ID. */
                          __(
                            "Restore snapshot #%d (state before this)",
                            "mcp-builder",
                          ),
                          entry.snapshotId,
                        )}
                      </Button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mcpb-pagination">
            <Button
              variant="secondary"
              size="compact"
              disabled={page <= 1}
              onClick={() => setPage(page - 1)}
            >
              {__("Previous", "mcp-builder")}
            </Button>
            <span>
              {sprintf(
                /* translators: 1: current page, 2: total pages. */
                __("Page %1$d of %2$d", "mcp-builder"),
                page,
                pages,
              )}
            </span>
            <Button
              variant="secondary"
              size="compact"
              disabled={page >= pages}
              onClick={() => setPage(page + 1)}
            >
              {__("Next", "mcp-builder")}
            </Button>
          </div>
        </>
      )}
    </Section>
  );
};

const Snapshots = ({ version }: { version: number }) => {
  const [items, setItems] = useState<Snapshot[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const load = useCallback(() => {
    getSnapshots()
      .then(setItems)
      .catch((e) => setError(errorMessage(e)));
  }, []);

  useEffect(load, [load, version]);

  const restore = async (id: number) => {
    if (!confirmRestore(id)) {
      return;
    }
    try {
      const result = await restoreSnapshot(id);
      setMessage(
        sprintf(
          /* translators: 1: restored snapshot ID, 2: undo snapshot ID. */
          __("Restored snapshot #%1$d. To undo, restore #%2$d.", "mcp-builder"),
          result.restored,
          result.undoSnapshotId,
        ),
      );
      load();
    } catch (e) {
      setError(errorMessage(e));
    }
  };

  return (
    <Section
      title={__("Recent snapshots", "mcp-builder")}
      description={__(
        "Automatic backups taken right before each change.",
        "mcp-builder",
      )}
    >
      {error && (
        <Notice status="error" onRemove={() => setError(null)}>
          {error}
        </Notice>
      )}
      {message && (
        <Notice status="success" onRemove={() => setMessage(null)}>
          {message}
        </Notice>
      )}
      {!items ? (
        <Spinner />
      ) : (
        <table className="widefat striped mcpb-table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">{__("When", "mcp-builder")}</th>
              <th scope="col">{__("What", "mcp-builder")}</th>
              <th scope="col">{__("Taken before", "mcp-builder")}</th>
              <th scope="col">
                <span className="screen-reader-text">
                  {__("Actions", "mcp-builder")}
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 && (
              <tr>
                <td colSpan={5}>{__("No snapshots yet.", "mcp-builder")}</td>
              </tr>
            )}
            {items.map((item) => (
              <tr key={item.id}>
                <td>{item.id}</td>
                <td>{formatDate(item.createdAt)}</td>
                <td>
                  {item.targetType === "post"
                    ? sprintf(
                        /* translators: %d: page ID. */
                        __("Page %d", "mcp-builder"),
                        item.targetId,
                      )
                    : item.targetKey}
                </td>
                <td>{item.label || item.ability}</td>
                <td>
                  <Button variant="link" onClick={() => restore(item.id)}>
                    {__("Restore", "mcp-builder")}
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </Section>
  );
};

export const SafetyTab = () => {
  const { settings, save, saving, error, clearError } = useSettings();
  const [restores, setRestores] = useState(0);

  if (!settings) {
    return error ? (
      <Notice status="error" isDismissible={false}>
        {error}
      </Notice>
    ) : (
      <Spinner />
    );
  }

  return (
    <>
      {error && (
        <Notice status="error" onRemove={clearError}>
          {error}
        </Notice>
      )}
      <SettingsForm settings={settings} save={save} saving={saving} />
      <Snapshots version={restores} />
      <AuditLog onRestored={() => setRestores(restores + 1)} />
    </>
  );
};
