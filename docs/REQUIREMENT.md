# MCP 化検討ドキュメント — **ラッパー方式**で php‑class‑diagram を利用

> **目的**: 既存の `php‑class‑diagram` 本体を改修せず、**薄いラッパープロジェクト**として Model Context Protocol (MCP) に対応させる際の、本質的な設計・実装・配布指針をまとめる。

---

## 1. MCP とは何か（再確認）

| 観点       | 内容                                                     |
| -------- | ------------------------------------------------------ |
| **主体**   | LLM／エージェントから呼び出される外部ツール群                               |
| **入出力**  | JSON‐RPC 互換のメッセージで *構造化* データをやりとりするのが基本（バイナリも可だが推奨しない） |
| **コア価値** | *LLM が「自分の思考を加速する補助情報」をオンデマンド取得* できるようにすること            |
| **前提条件** | ツール呼び出しは *副作用が小さい*（読取専用）・*決定論的* であることが望ましい▽んt          |

---

## 2. LLM がクラス図を欲しがる 5 つの状況（再掲）

1. **明示的生成要求** — ユーザが「図を作って」と言う場合。
2. **設計評価補助** — SOLID 逸脱・責務過多チェック等の際、依存関係を視覚化すると判断した場合。
3. **リファクタ提案** — 継承→委譲やクラス分割等のアドバイスを下すための現状把握。
4. **他ツール結果の補完** — 型解析・未使用コード検出など別 MCP の結果を補強するため依存グラフが必要。
5. **ドキュメント自動生成** — README/ADR に図を自動挿入すると判断したとき。

⇒ **結論**: *図を得ること自体* が目的になるより、**分析・説明の精度向上** のために呼び出されるケースが多い。

---

## 3. ラッパー MCP で提供すべき機能

| 目的            | 実装アイデア                                                                                  |
| ------------- | --------------------------------------------------------------------------------------- |
| **クラス図出力**    | `php‑class‑diagram` を子プロセス呼び出しし、生成された PlantUML テキストをそのまま MCP レスポンスとして返却。追加の JSON 生成は不要。 |
| **引数正規化**     | MCP の `directoryPath` や `exclude`, `depth` などを CLI オプションへマッピング。                         |
| **ストリーミング対応** | 大規模リポジトリではチャンクで送信。`{"chunk":1,"data":"@startuml..."}` 形式の JSON-RPC ストリームを想定。            |
| **エラーラッピング**  | php‑class‑diagram の exitCode と stderr を解析し、MCP 仕様の `error` オブジェクトに変換。                   |
| **キャッシュ**     | 同一入力に対しハッシュキーで結果をキャッシュし、性能を確保。                                                          |

---

## 4. 実装アーキテクチャ（ラッパープロジェクト）

| レイヤ        | 推奨構成                                                                                                              | 補足                                         |
| ---------- | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| **呼び出し形態** | 1. \*\*CLI ラッパー (PHAR/Composer)\*\*2. **HTTP+JSON RPC Docker イメージ**                                               | *LLM サンドボックスでは CLI が簡単*。クラウド統合では HTTP が便利。 |
| **内部実装**   | PHP 8.4 スクリプトで`proc_open()` & `symfony/process` などを利用して子プロセス実行                                                    | `timeout` と `memory_limit` を必ず設定。          |
| **配置例**    | `php-class-diagram-mcp/` リポジトリ  ├ bin/handler.php  ├ composer.json (require php‑class‑diagram ^X.Y)  └ Dockerfile | CI で PHAR と Docker イメージを生成・公開。             |

---

## 5. テスト & CI 戦略

1. **E2E テスト** — モックリポジトリを用意し、ラッパー経由で LLM 期待形式のレスポンスが返るか確認。
2. **コンカレント実行テスト** — 子プロセス同時実行時のリソース競合を検証。
3. **性能ベンチ** — 10k ファイル規模で実行時間・メモリを計測し、キャッシュ効果を測定。
4. **契約テスト** — JSON-RPC スキーマとエラーモデルをスナップショットで固定。

---

## 6. セキュリティ & ガバナンス

* **読取専用**ポリシーをラッパー側で enforce。書込み系オプションは無効化。
* `--safe-mode` をデフォルトにし、外部 include/require を防ぐ。
* オープンソースライセンス (MIT / Apache‑2.0) と SPDX 識別子を明示。

---

## 7. 将来拡張ロードマップ

1. **差分モード対応** — MCP 呼び出し時に `--baseline` を指定し、Git 2 点間の差分図を生成。
2. **複数フォーマット出力** — 将来的に JSON が欲しいツール向けに `--format=json` をラッパーが変換提供。
3. **サブプロジェクト統合** — 他の PHP 解析ツール（型チェック、カバレッジ）も同一 MCP ラッパー規約で統合。

---

## 8. まとめ

* **本体改修なし**で MCP 化できるため、既存ユーザー影響ゼロ。
* ラッパーは *子プロセス実行・レスポンス整形* だけに責務を絞ることで、高い保守性と拡張性を両立。
* 段階的に HTTP / Docker に拡張し、AI ネイティブ開発フローのプラグアンドプレイ部品として機能させる。

---

## 9. Appendix — `manifest.json` (初期案)

> LLM が自律的に「クラス図が欲しい」と判断できるよう、**呼び出しトリガーのヒント**と**入出力スキーマ**を詳細に記述。

```json
{
  "name": "php-class-diagram",
  "version": "1.0.0",
  "description": "Generate PlantUML class diagram from PHP source directories (read‑only). Intended for LLM agents to analyse structure, detect design smells, or document architecture.",
  "tags": ["php", "uml", "class-diagram", "architecture-analysis"],

  "entry_point": "php bin/handler.php",
  "runtime": "php >=8.4",
  "timeout_seconds": 90,
  "streaming": true,
  "side_effects": "none",

  "input_schema": {
    "type": "object",
    "required": ["directory"],
    "properties": {
      "directory": {
        "type": "string",
        "description": "Absolute or workspace‑relative path to the PHP project root to analyse."
      },
      "exclude": {
        "type": "array",
        "items": { "type": "string" },
        "description": "Optional glob patterns to exclude paths."
      },
      "depth": {
        "type": "integer",
        "minimum": 0,
        "description": "Optional maximum nesting depth (0 = unlimited)."
      }
    }
  },

  "output_schema": {
    "type": "object",
    "required": ["plantuml"],
    "properties": {
      "plantuml": {
        "type": "string",
        "description": "PlantUML class diagram script representing classes, interfaces, traits and their relationships."
      },
      "classCount": { "type": "integer" },
      "interfaceCount": { "type": "integer" }
    }
  },

  "auto_activation_hints": {
    "trigger_keywords": [
      "class diagram",
      "PlantUML",
      "UML class diagram",
      "architecture diagram",
      "dependency graph",
      "circular dependency",
      "SOLID violation",
      "refactor classes",
      "inheritance hierarchy",
      "impact analysis",
      "change propagation",
      "ripple effect"
    ],
    "purpose_summary": "Use when a structural or dependency-level overview of a PHP codebase would improve analysis, refactoring, or impact assessment. Particularly useful when estimating what parts of the code may be affected by a class/interface change."
  }
}
```

> ⚠️ **備考**: フィールド名や構造は MCP 実装固有の制約に合わせて調整してください。`auto_activation_hints` は LLM が「適切な場面で自律的に呼び出す」ための目安となります。

