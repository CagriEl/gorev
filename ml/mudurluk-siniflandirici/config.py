"""Ortam değişkenleri ve yol ayarları."""

from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict

BASE_DIR = Path(__file__).resolve().parent


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=(BASE_DIR / ".env", BASE_DIR.parent.parent / ".env"),
        env_file_encoding="utf-8",
        extra="ignore",
    )

    # Veritabanı (Gorev ile uyumlu isimler)
    db_connection: str = "mysql"
    db_host: str = "127.0.0.1"
    db_port: int = 3306
    db_database: str = "gorev"
    db_username: str = "root"
    db_password: str = ""

    # Model
    model_name: str = "dbmdz/bert-base-turkish-cased"
    model_dir: Path = BASE_DIR / "kaydedilen_model"
    confidence_threshold: float = 0.55

    # API
    api_host: str = "127.0.0.1"
    api_port: int = 8100
    api_key: str = ""

    # Eğitim
    train_epochs: int = 3
    train_batch_size: int = 8
    max_length: int = 128
    test_size: float = 0.2
    random_seed: int = 42

    @property
    def database_url(self) -> str:
        if self.db_connection == "sqlite":
            path = Path(self.db_database)
            if not path.is_absolute():
                path = (BASE_DIR.parent.parent / path).resolve()
            return f"sqlite:///{path}"

        return (
            f"mysql+pymysql://{self.db_username}:{self.db_password}"
            f"@{self.db_host}:{self.db_port}/{self.db_database}"
        )


def get_settings() -> Settings:
    return Settings()
